<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Echeance;
use App\Models\Facture;
use App\Models\RendezVousEnLigne;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TableauDeBordController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        return response()->json([
            'ca_total' => $this->caTotal(),
            'ca_ce_mois' => $this->caPeriode(now()->startOfMonth(), now()->endOfMonth()),
            'ca_cette_annee' => $this->caPeriode(now()->startOfYear(), now()->endOfYear()),
            'factures_impayees' => $this->facturesImpayees(),
            'ca_par_avocat' => $this->caParAvocat(),
            'evolution_mensuelle' => $this->evolutionMensuelle(),
            'dossiers_par_statut' => $this->dossiersParStatut(),
            'dossiers_par_type' => $this->dossiersParType(),
            'nombre_clients' => Client::count(),
            'audiences_a_venir' => Echeance::where('type', 'audience')->where('statut', 'a_venir')->where('date_heure', '>', now())->count(),
            'echeances_a_venir' => Echeance::where('statut', 'a_venir')->where('date_heure', '>', now())->count(),
            'rendez_vous' => $this->rendezVous(),
        ]);
    }

    private function caTotal(): float
    {
        return (float) Facture::where('statut', 'payee')->sum('montant_ttc');
    }

    private function caPeriode(Carbon $debut, Carbon $fin): float
    {
        return (float) Facture::where('statut', 'payee')
            ->whereBetween('date_emission', [$debut, $fin])
            ->sum('montant_ttc');
    }

    /** Montant total et nombre de factures envoyées mais pas encore payées (ni annulées). */
    private function facturesImpayees(): array
    {
        $requete = Facture::where('statut', 'envoyee');

        return [
            'montant' => (float) (clone $requete)->sum('montant_ttc'),
            'nombre' => $requete->count(),
        ];
    }

    /** Chiffre d'affaires (factures payées) regroupé par avocat responsable du dossier. */
    private function caParAvocat(): array
    {
        return Facture::where('factures.statut', 'payee')
            ->join('dossiers', 'dossiers.id', '=', 'factures.dossier_id')
            ->join('users', 'users.id', '=', 'dossiers.avocat_id')
            ->selectRaw('users.name as avocat, SUM(factures.montant_ttc) as total')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($ligne) => ['avocat' => $ligne->avocat, 'total' => (float) $ligne->total])
            ->toArray();
    }

    /** Chiffre d'affaires des 12 derniers mois (factures payées), pour un graphique d'évolution. */
    private function evolutionMensuelle(): array
    {
        $debut = now()->subMonths(11)->startOfMonth();

        // Regroupement fait côté PHP plutôt qu'en SQL brut (DATE_FORMAT est
        // spécifique à MySQL et casse sur SQLite, utilisé pour les tests).
        $facturesParMois = Facture::where('statut', 'payee')
            ->where('date_emission', '>=', $debut)
            ->get(['date_emission', 'montant_ttc'])
            ->groupBy(fn ($facture) => $facture->date_emission->format('Y-m'))
            ->map(fn ($groupe) => $groupe->sum('montant_ttc'));

        $resultat = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $cle = $date->format('Y-m');
            $resultat[] = [
                'mois' => $date->translatedFormat('M Y'),
                'total' => (float) ($facturesParMois[$cle] ?? 0),
            ];
        }

        return $resultat;
    }

    private function dossiersParStatut(): array
    {
        return Dossier::selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();
    }

    private function dossiersParType(): array
    {
        return Dossier::selectRaw('type_affaire, COUNT(*) as total')
            ->groupBy('type_affaire')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($ligne) => ['type' => $ligne->type_affaire, 'total' => $ligne->total])
            ->toArray();
    }

    private function rendezVous(): array
    {
        return [
            'a_venir' => RendezVousEnLigne::where('statut', 'confirme')->where('date_heure', '>', now())->count(),
            'en_attente' => RendezVousEnLigne::where('statut', 'demande')->count(),
            'total_confirmes' => RendezVousEnLigne::where('statut', 'confirme')->count(),
            'total_annules' => RendezVousEnLigne::where('statut', 'annule')->count(),
        ];
    }
}
