<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmationRendezVousMail;
use App\Models\Client;
use App\Models\Echeance;
use App\Models\RendezVousEnLigne;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Endpoints PUBLICS (pas d'authentification) consommés par le widget de prise
 * de rendez-vous du site vitrine du cabinet.
 */
class RendezVousPublicController extends Controller
{
    /** Liste publique des avocats du cabinet, pour le sélecteur du widget de prise de RDV. */
    public function avocats()
    {
        return response()->json(
            User::where('role', 'avocat')->select('id', 'name')->orderBy('name')->get()
        );
    }

    /**
     * Calcule les créneaux disponibles d'un avocat sur une plage de dates,
     * en excluant les rendez-vous déjà pris et les échéances existantes.
     * Horaires de travail simplifiés : 9h-12h30 / 14h-18h, créneaux de 30 min.
     */
    public function creneauxDisponibles(Request $request)
    {
        $data = $request->validate([
            'avocat_id' => 'required|exists:users,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        $avocat = User::findOrFail($data['avocat_id']);
        $debut = Carbon::parse($data['date_debut'])->startOfDay();
        $fin = Carbon::parse($data['date_fin'])->endOfDay();

        $occupes = collect();
        RendezVousEnLigne::where('avocat_id', $avocat->id)
            ->where('statut', '!=', 'annule')
            ->whereBetween('date_heure', [$debut, $fin])
            ->pluck('date_heure')
            ->each(fn ($d) => $occupes->push($d->format('Y-m-d H:i')));

        Echeance::whereHas('dossier', fn ($q) => $q->where('avocat_id', $avocat->id)->orWhere('assistant_id', $avocat->id))
            ->whereBetween('date_heure', [$debut, $fin])
            ->pluck('date_heure')
            ->each(fn ($d) => $occupes->push(Carbon::parse($d)->format('Y-m-d H:i')));

        $creneaux = [];
        for ($jour = $debut->copy(); $jour->lte($fin); $jour->addDay()) {
            if ($jour->isWeekend()) {
                continue;
            }
            foreach (['09:00-12:30', '14:00-18:00'] as $plage) {
                [$h1, $h2] = explode('-', $plage);
                $curseur = $jour->copy()->setTimeFromTimeString($h1);
                $limite = $jour->copy()->setTimeFromTimeString($h2);

                while ($curseur->lt($limite)) {
                    if ($curseur->gt(now()) && ! $occupes->contains($curseur->format('Y-m-d H:i'))) {
                        $creneaux[] = $curseur->toIso8601String();
                    }
                    $curseur->addMinutes(30);
                }
            }
        }

        return response()->json($creneaux);
    }

    /**
     * Réserve un créneau : crée (ou retrouve) automatiquement la fiche client,
     * enregistre la demande de rendez-vous, et envoie un email de confirmation.
     */
    public function reserver(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email',
            'telephone' => 'nullable|string|max:30',
            'motif' => 'nullable|string|max:255',
            'avocat_id' => 'required|exists:users,id',
            'date_heure' => 'required|date|after:now',
        ]);

        $conflit = RendezVousEnLigne::where('avocat_id', $data['avocat_id'])
            ->where('date_heure', $data['date_heure'])
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($conflit) {
            return response()->json(['message' => "Ce créneau vient d'être réservé, merci d'en choisir un autre."], 409);
        }

        // Recherche une fiche client existante par email, sinon en crée une automatiquement.
        [$prenom, $nom] = array_pad(explode(' ', $data['nom'], 2), 2, '');
        $client = Client::firstOrCreate(
            ['email' => $data['email']],
            ['type' => 'particulier', 'prenom' => $prenom, 'nom' => $nom, 'telephone' => $data['telephone'] ?? null]
        );

        $rendezVous = RendezVousEnLigne::create([
            'nom' => $data['nom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'] ?? null,
            'motif' => $data['motif'] ?? null,
            'avocat_id' => $data['avocat_id'],
            'client_id' => $client->id,
            'date_heure' => $data['date_heure'],
            'statut' => 'demande',
        ]);

        Mail::to($data['email'])->send(new ConfirmationRendezVousMail($rendezVous->load('avocat')));

        return response()->json($rendezVous->load('avocat'), 201);
    }
}
