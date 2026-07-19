<?php

namespace App\Services;

use App\Mail\FactureMail;
use App\Models\Dossier;
use App\Models\Facture;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Centralise la génération d'un mémoire d'honoraires pour un dossier, qu'elle soit
 * déclenchée manuellement (bouton "Générer une facture"), automatiquement à la
 * clôture du dossier, ou périodiquement par la commande facture:generer-periodiques.
 */
class FacturationAutomatique
{
    /**
     * @return Facture|null La facture créée, ou null si rien n'était à facturer
     *                       (aucun temps non facturé en mode horaire).
     */
    public static function genererPourDossier(Dossier $dossier, bool $envoyerParEmail = false): ?Facture
    {
        $facture = DB::transaction(function () use ($dossier) {
            if ($dossier->mode_facturation === 'forfait') {
                return self::genererFactureForfait($dossier);
            }

            return self::genererFactureHoraire($dossier);
        });

        if ($facture && $envoyerParEmail && $dossier->client->email) {
            Mail::to($dossier->client->email)->send(new FactureMail($facture));
            $facture->update(['statut' => 'envoyee']);
        }

        if ($facture) {
            $dossier->update(['derniere_facturation_auto_le' => now()]);
        }

        return $facture;
    }

    /** Ajoute les déboursés non encore facturés (frais de cour, déplacements,
     * photocopies...) comme lignes supplémentaires — peu importe le mode de
     * facturation (horaire ou forfaitaire), ce sont des frais réels à
     * rembourser au cabinet, distincts des honoraires eux-mêmes. */
    private static function ajouterDebourses(Facture $facture, Dossier $dossier): void
    {
        $debourses = $dossier->debourseNonFactures()->get();

        foreach ($debourses as $debourse) {
            $facture->lignes()->create([
                'description' => "Déboursé — {$debourse->description}",
                'quantite' => 1,
                'prix_unitaire' => $debourse->montant,
                'montant' => $debourse->montant,
            ]);
        }

        $debourses->each->update(['facture_id' => $facture->id]);
    }

    private static function genererFactureForfait(Dossier $dossier): Facture
    {
        $facture = Facture::create([
            'numero' => self::genererNumero(),
            'dossier_id' => $dossier->id,
            'client_id' => $dossier->client_id,
            'date_emission' => now()->toDateString(),
            'date_echeance' => now()->addDays(30)->toDateString(),
            'taux_tps' => 5, 'taux_tvq' => 9.975, // Québec : calculées indépendamment (pas de taxe sur taxe depuis 2013)
            'statut' => 'brouillon',
        ]);

        $facture->lignes()->create([
            'description' => "Honoraires forfaitaires — {$dossier->titre}",
            'quantite' => 1,
            'prix_unitaire' => $dossier->montant_forfait ?? 0,
            'montant' => $dossier->montant_forfait ?? 0,
        ]);

        self::ajouterDebourses($facture, $dossier);

        $facture->recalculerMontants();

        return $facture;
    }

    private static function genererFactureHoraire(Dossier $dossier): ?Facture
    {
        $tempsParUtilisateur = $dossier->tempsNonFactures()->with('user')->get()->groupBy('user_id');
        $debourses = $dossier->debourseNonFactures()->get();

        if ($tempsParUtilisateur->isEmpty() && $debourses->isEmpty()) {
            return null;
        }

        $facture = Facture::create([
            'numero' => self::genererNumero(),
            'dossier_id' => $dossier->id,
            'client_id' => $dossier->client_id,
            'date_emission' => now()->toDateString(),
            'date_echeance' => now()->addDays(30)->toDateString(),
            'taux_tps' => 5, 'taux_tvq' => 9.975, // Québec : calculées indépendamment (pas de taxe sur taxe depuis 2013)
            'statut' => 'brouillon',
        ]);

        foreach ($tempsParUtilisateur as $entrees) {
            $utilisateur = $entrees->first()->user;
            $taux = $dossier->taux_horaire
                ?? $utilisateur->taux_horaire_defaut
                ?? \App\Models\CabinetSetting::instance()->taux_horaire_defaut
                ?? 0;
            $heures = round($entrees->sum('duree_secondes') / 3600, 2);

            $facture->lignes()->create([
                'description' => "Temps passé — {$utilisateur->name} ({$heures} h)",
                'quantite' => $heures,
                'prix_unitaire' => $taux,
                'montant' => round($heures * $taux, 2),
            ]);

            $entrees->each->update(['facture_id' => $facture->id, 'taux_horaire_applique' => $taux]);
        }

        self::ajouterDebourses($facture, $dossier);

        $facture->recalculerMontants();

        return $facture;
    }

    private static function genererNumero(): string
    {
        $annee = now()->year;
        // MAX du suffixe numérique, pas count() : si une facture est supprimée
        // au milieu de la séquence (ex: la n°3), count() se décale et regénère
        // un numéro qui existe déjà plus loin (ex: reproduit la n°6 existante).
        $dernierNumero = Facture::where('numero', 'like', "FAC-{$annee}-%")
            ->get()
            ->map(fn ($f) => (int) substr($f->numero, -4))
            ->max() ?? 0;

        return sprintf('FAC-%d-%04d', $annee, $dernierNumero + 1);
    }
}
