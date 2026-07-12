<?php

namespace App\Console\Commands;

use App\Models\Dossier;
use App\Services\FacturationAutomatique;
use Illuminate\Console\Command;

/**
 * Génère et envoie automatiquement les mémoires d'honoraires des dossiers en
 * facturation périodique (hebdomadaire ou mensuelle), pour tout le temps
 * facturable pas encore facturé. Prévu pour tourner via le scheduler Laravel
 * (voir routes/console.php ou bootstrap/app.php selon la version).
 *
 *   php artisan factures:generer-periodiques
 */
class GenererFacturesPeriodiques extends Command
{
    protected $signature = 'factures:generer-periodiques {--dry-run : Simule sans créer ni envoyer de facture}';

    protected $description = "Génère et envoie les mémoires d'honoraires des dossiers en facturation périodique";

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $dossiers = Dossier::query()
            ->where('facturation_periodique', true)
            ->where('mode_facturation', 'horaire')
            ->whereIn('statut', ['ouvert', 'en_cours', 'en_attente'])
            ->get()
            ->filter(fn (Dossier $d) => $this->echeancePeriodeAtteinte($d));

        if ($dossiers->isEmpty()) {
            $this->info('Aucun dossier à facturer pour le moment.');

            return self::SUCCESS;
        }

        foreach ($dossiers as $dossier) {
            if ($dossier->tempsNonFactures()->doesntExist()) {
                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] Facturerait le dossier {$dossier->reference} ({$dossier->titre})");

                continue;
            }

            $facture = FacturationAutomatique::genererPourDossier($dossier, envoyerParEmail: true);

            $this->info($facture
                ? "Facture {$facture->numero} générée et envoyée pour {$dossier->reference}"
                : "Rien à facturer pour {$dossier->reference}");
        }

        return self::SUCCESS;
    }

    /** Vrai si la périodicité choisie (hebdo/mensuelle) est écoulée depuis la dernière facturation auto. */
    private function echeancePeriodeAtteinte(Dossier $dossier): bool
    {
        if (! $dossier->derniere_facturation_auto_le) {
            return true;
        }

        $joursMinimum = $dossier->frequence_facturation === 'hebdomadaire' ? 7 : 30;

        return $dossier->derniere_facturation_auto_le->diffInDays(now()) >= $joursMinimum;
    }
}
