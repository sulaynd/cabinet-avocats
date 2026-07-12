<?php

namespace Database\Factories;

use App\Models\RendezVousEnLigne;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RendezVousEnLigneFactory extends Factory
{
    protected $model = RendezVousEnLigne::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'telephone' => $this->faker->phoneNumber(),
            'motif' => $this->faker->sentence(),
            'avocat_id' => User::factory()->avocat(),
            'date_heure' => now()->addDays($this->faker->numberBetween(1, 14)),
            'statut' => 'demande',
        ];
    }

    public function confirme(): static
    {
        return $this->state(fn () => ['statut' => 'confirme']);
    }

    public function annule(): static
    {
        return $this->state(fn () => ['statut' => 'annule']);
    }
}
