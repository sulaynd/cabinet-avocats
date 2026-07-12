<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\FactureMail;
use App\Models\Dossier;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class FactureController extends Controller
{
    public function index(Request $request)
    {
        $factures = Facture::query()
            ->with(['client', 'dossier'])
            // Un avocat/assistant ne voit que les factures des dossiers qui lui
            // sont assignés ; un admin voit tout.
            ->whereHas('dossier', fn ($q) => $q->visiblePar($request->user()))
            ->when($request->statut, fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->client_id, fn ($q, $id) => $q->where('client_id', $id))
            ->when($request->dossier_id, fn ($q, $id) => $q->where('dossier_id', $id))
            ->orderByDesc('date_emission')
            ->paginate($request->per_page ?? 20);

        return response()->json($factures);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'dossier_id' => 'required|exists:dossiers,id',
            'client_id' => 'required|exists:clients,id',
            'date_emission' => 'required|date',
            'date_echeance' => 'nullable|date',
            'taux_tva' => 'nullable|numeric|min:0|max:100',
            'lignes' => 'required|array|min:1',
            'lignes.*.description' => 'required|string|max:255',
            'lignes.*.quantite' => 'required|numeric|min:0',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        $dossierCible = \App\Models\Dossier::findOrFail($data['dossier_id']);
        abort_unless($request->user()->can('view', $dossierCible), 403, "Ce dossier ne vous est pas assigné.");

        $facture = DB::transaction(function () use ($data) {
            $facture = Facture::create([
                'numero' => $this->genererNumero(),
                'dossier_id' => $data['dossier_id'],
                'client_id' => $data['client_id'],
                'date_emission' => $data['date_emission'],
                'date_echeance' => $data['date_echeance'] ?? null,
                'taux_tva' => $data['taux_tva'] ?? 20,
                'statut' => 'brouillon',
            ]);

            foreach ($data['lignes'] as $ligne) {
                $facture->lignes()->create([
                    'description' => $ligne['description'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'montant' => $ligne['quantite'] * $ligne['prix_unitaire'],
                ]);
            }

            $facture->recalculerMontants();

            return $facture;
        });

        return response()->json($facture->load('lignes'), 201);
    }

    public function show(Request $request, Facture $facture)
    {
        abort_unless($request->user()->can('view', $facture->dossier), 403, "Cette facture appartient à un dossier qui ne vous est pas assigné.");

        return response()->json($facture->load(['lignes', 'client', 'dossier']));
    }

    public function update(Request $request, Facture $facture)
    {
        abort_unless($request->user()->can('view', $facture->dossier), 403, "Cette facture appartient à un dossier qui ne vous est pas assigné.");

        $data = $request->validate([
            'date_emission' => 'date',
            'date_echeance' => 'nullable|date',
            'taux_tva' => 'numeric|min:0|max:100',
            'statut' => [Rule::in(['brouillon', 'envoyee', 'payee', 'en_retard', 'annulee'])],
        ]);

        $facture->update($data);
        $facture->recalculerMontants();

        return response()->json($facture->load('lignes'));
    }

    public function marquerPayee(Request $request, Facture $facture)
    {
        abort_unless($request->user()->can('view', $facture->dossier), 403, "Cette facture appartient à un dossier qui ne vous est pas assigné.");

        $facture->update(['statut' => 'payee']);

        return response()->json($facture);
    }

    /**
     * Automatise la création d'un mémoire d'honoraires pour un dossier (bouton manuel).
     * Voir App\Services\FacturationAutomatique pour la même logique appliquée
     * automatiquement à la clôture du dossier ou périodiquement.
     */
    public function genererDepuisTemps(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $request->validate(['envoyer_par_email' => 'boolean']);

        $facture = \App\Services\FacturationAutomatique::genererPourDossier(
            $dossier,
            $request->boolean('envoyer_par_email')
        );

        if (! $facture) {
            abort(422, 'Aucun temps facturable non encore facturé sur ce dossier.');
        }

        return response()->json($facture->load('lignes'), 201);
    }

    public function destroy(Request $request, Facture $facture)
    {
        abort_unless($request->user()->can('view', $facture->dossier), 403, "Cette facture appartient à un dossier qui ne vous est pas assigné.");

        $facture->delete();

        return response()->json(null, 204);
    }

    /**
     * Génère et retourne le PDF de la facture (téléchargement direct).
     * Nécessite le package : composer require barryvdh/laravel-dompdf
     */
    public function genererPdf(Request $request, Facture $facture)
    {
        abort_unless($request->user()->can('view', $facture->dossier), 403, "Cette facture appartient à un dossier qui ne vous est pas assigné.");

        $facture->load(['lignes', 'client', 'dossier']);

        $pdf = Pdf::loadView('pdf.facture', ['facture' => $facture])
            ->setPaper('a4');

        return $pdf->download("{$facture->numero}.pdf");
    }

    /** Envoie la facture par email au client (PDF en pièce jointe) et la passe au statut "envoyée". */
    public function envoyer(Request $request, Facture $facture)
    {
        abort_unless($request->user()->can('view', $facture->dossier), 403, "Cette facture appartient à un dossier qui ne vous est pas assigné.");

        if (! $facture->client->email) {
            return response()->json(['message' => 'Ce client ne possède pas d\'adresse email enregistrée.'], 422);
        }

        Mail::to($facture->client->email)->send(new FactureMail($facture));

        if ($facture->statut === 'brouillon') {
            $facture->update(['statut' => 'envoyee']);
        }

        return response()->json($facture->fresh());
    }

    private function genererNumero(): string
    {
        $annee = now()->year;
        $dernier = Facture::where('numero', 'like', "FAC-{$annee}-%")->count() + 1;

        return sprintf('FAC-%d-%04d', $annee, $dernier);
    }
}
