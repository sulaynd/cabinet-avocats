<?php

namespace Database\Factories;

use App\Models\Debourse;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebourseFactory extends Factory
{
    protected $model = Debourse::class;

    public function definition(): array
    {
        return [
            'dossier_id' => Dossier::factory(),
            'user_id' => User::factory(),
            'categorie' => $this->faker->randomElement(['frais_cour', 'deplacement', 'photocopie', 'autre']),
            'description' => $this->faker->sentence(3),
            'montant' => $this->faker->randomFloat(2, 5, 500),
            'date_debourse' => now(),
            'facture_id' => null,
        ];
    }
}
