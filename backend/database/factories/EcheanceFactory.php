<?php

namespace Database\Factories;

use App\Models\Dossier;
use App\Models\Echeance;
use Illuminate\Database\Eloquent\Factories\Factory;

class EcheanceFactory extends Factory
{
    protected $model = Echeance::class;

    public function definition(): array
    {
        return [
            'dossier_id' => Dossier::factory(),
            'titre' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['audience', 'delai_procedural', 'rdv_client', 'autre']),
            'date_heure' => now()->addDays($this->faker->numberBetween(1, 30)),
            'lieu' => $this->faker->address(),
            'statut' => 'a_venir',
        ];
    }

    public function audience(): static
    {
        return $this->state(fn () => ['type' => 'audience']);
    }

    public function realisee(): static
    {
        return $this->state(fn () => ['statut' => 'realisee']);
    }
}
