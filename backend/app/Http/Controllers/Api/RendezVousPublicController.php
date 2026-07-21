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
use Illuminate\Validation\Rule;

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
     * Calcule les créneaux disponibles sur une plage de dates, selon les
     * horaires standards du cabinet (9h-12h30 / 14h-18h, créneaux de 30 min).
     * Le client ne choisissant plus d'avocat précis à cette étape (voir
     * reserver() ci-dessous), ces créneaux restent génériques — c'est au
     * cabinet de vérifier la disponibilité réelle de l'avocat assigné au
     * moment de confirmer la demande.
     */
    public function creneauxDisponibles(Request $request)
    {
        $data = $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        $debut = Carbon::parse($data['date_debut'])->startOfDay();
        $fin = Carbon::parse($data['date_fin'])->endOfDay();

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
                    if ($curseur->gt(now())) {
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
     * enregistre la demande de rendez-vous (sans avocat assigné — voir
     * RendezVousController::confirmer() pour l'assignation par le cabinet),
     * et envoie un email de confirmation.
     */
    public function reserver(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email',
            'telephone' => 'nullable|string|max:30',
            'adresse' => 'nullable|string|max:255',
            'code_postal' => 'nullable|string|max:20',
            'ville' => 'nullable|string|max:255',
            'type_affaire' => ['required', Rule::in(\App\Models\TypeAffaire::pluck('slug'))],
            'sous_categories_affaire' => 'nullable|array',
            'sous_categories_affaire.*' => Rule::in(\App\Models\SousCategorieAffaire::pluck('slug')),
            'motif' => 'required|string|max:255',
            'date_heure' => 'required|date|after:now',
        ]);

        // Au moins une sous-catégorie est obligatoire uniquement si le type
        // d'affaire choisi a des sous-catégories actives définies.
        $typeAffaire = \App\Models\TypeAffaire::where('slug', $data['type_affaire'])->first();
        $possedeSousCategories = $typeAffaire && $typeAffaire->sousCategories()->where('actif', true)->exists();
        abort_if(
            $possedeSousCategories && empty($data['sous_categories_affaire'] ?? null),
            422,
            "Au moins une sous-catégorie est obligatoire pour le type d'affaire '{$typeAffaire?->libelle}'.",
        );

        // Recherche une fiche client existante par email, sinon en crée une automatiquement.
        [$prenom, $nom] = array_pad(explode(' ', $data['nom'], 2), 2, '');
        $client = Client::firstOrCreate(
            ['email' => $data['email']],
            [
                'type' => 'particulier',
                'prenom' => $prenom,
                'nom' => $nom,
                'telephone' => $data['telephone'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'code_postal' => $data['code_postal'] ?? null,
                'ville' => $data['ville'] ?? null,
            ]
        );

        $rendezVous = RendezVousEnLigne::create([
            'nom' => $data['nom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'] ?? null,
            'motif' => $data['motif'],
            'type_affaire' => $data['type_affaire'],
            'sous_categories_affaire' => $data['sous_categories_affaire'] ?? null,
            'avocat_id' => null,
            'client_id' => $client->id,
            'date_heure' => $data['date_heure'],
            'statut' => 'demande',
        ]);

        Mail::to($data['email'])->send(new ConfirmationRendezVousMail($rendezVous));

        return response()->json($rendezVous, 201);
    }
}
