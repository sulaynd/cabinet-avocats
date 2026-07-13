<?php

namespace Database\Factories;

use App\Models\Dossier;
use App\Models\TempsPasse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TempsPasseFactory extends Factory
{
    protected $model = TempsPasse::class;

    public function definition(): array
    {
        return [
            'dossier_id' => Dossier::factory(),
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(),
            'demarre_a' => now()->subHour(),
            'termine_a' => now(),
            'duree_secondes' => 3600,
            'facturable' => true,
            'taux_horaire_applique' => 150,
            'facture_id' => null,
        ];
    }

    public function enCours(): static
    {
        return $this->state(fn () => ['demarre_a' => now(), 'termine_a' => null, 'duree_secondes' => 0]);
    }
}
