<?php

namespace Database\Factories;

use App\Models\Actualite;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActualiteFactory extends Factory
{
    protected $model = Actualite::class;

    public function definition(): array
    {
        return [
            'titre' => $this->faker->sentence(4),
            'date' => now(),
            'extrait' => $this->faker->paragraph(),
            'ordre' => 0,
            'actif' => false,
        ];
    }
}
