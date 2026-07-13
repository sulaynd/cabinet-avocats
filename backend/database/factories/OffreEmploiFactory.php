<?php

namespace Database\Factories;

use App\Models\OffreEmploi;
use Illuminate\Database\Eloquent\Factories\Factory;

class OffreEmploiFactory extends Factory
{
    protected $model = OffreEmploi::class;

    public function definition(): array
    {
        return [
            'titre' => $this->faker->jobTitle(),
            'description' => $this->faker->paragraph(),
            'type_contrat' => 'cdi',
            'lieu' => $this->faker->city(),
            'date_limite' => null,
            'ordre' => 0,
            'actif' => false,
        ];
    }
}
