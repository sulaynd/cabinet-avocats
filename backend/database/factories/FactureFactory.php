<?php

namespace Database\Factories;

use App\Models\Dossier;
use App\Models\Facture;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactureFactory extends Factory
{
    protected $model = Facture::class;

    public function definition(): array
    {
        $montantHt = $this->faker->randomFloat(2, 100, 2000);
        $tauxTps = 5;
        $tauxTvq = 9.975;
        $montantTps = round($montantHt * $tauxTps / 100, 2);
        $montantTvq = round($montantHt * $tauxTvq / 100, 2);

        return [
            'numero' => 'FAC-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'dossier_id' => Dossier::factory(),
            // Indépendant du dossier par défaut — dans un test qui a besoin que
            // client_id corresponde vraiment au client du dossier, passer les
            // deux explicitement : Facture::factory()->for($dossier)->create(['client_id' => $dossier->client_id])
            'client_id' => \App\Models\Client::factory(),
            'date_emission' => now()->toDateString(),
            'date_echeance' => now()->addDays(30)->toDateString(),
            'montant_ht' => $montantHt,
            'taux_tps' => $tauxTps,
            'taux_tvq' => $tauxTvq,
            'montant_tps' => $montantTps,
            'montant_tvq' => $montantTvq,
            'montant_ttc' => round($montantHt + $montantTps + $montantTvq, 2),
            'statut' => 'brouillon',
        ];
    }

    public function payee(): static
    {
        return $this->state(fn () => ['statut' => 'payee']);
    }

    public function envoyee(): static
    {
        return $this->state(fn () => ['statut' => 'envoyee']);
    }
}
