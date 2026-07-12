<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DossierFactory extends Factory
{
    protected $model = Dossier::class;

    public function definition(): array
    {
        return [
            'reference' => 'DOS-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'avocat_id' => User::factory()->avocat(),
            'assistant_id' => null,
            'titre' => $this->faker->sentence(4),
            'type_affaire' => $this->faker->randomElement(['civil', 'penal', 'commercial', 'famille', 'travail', 'immobilier', 'autre']),
            'statut' => 'ouvert',
            'mode_facturation' => 'horaire',
            'taux_horaire' => null,
            'montant_forfait' => null,
            'facturation_periodique' => false,
            'facturer_a_cloture' => false,
            'date_ouverture' => now()->toDateString(),
        ];
    }

    public function clos(): static
    {
        return $this->state(fn () => ['statut' => 'clos', 'date_cloture' => now()->toDateString()]);
    }

    public function forfait(float $montant = 500): static
    {
        return $this->state(fn () => ['mode_facturation' => 'forfait', 'montant_forfait' => $montant]);
    }
}
