<?php

namespace Database\Factories;

use App\Models\Communication;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunicationFactory extends Factory
{
    protected $model = Communication::class;

    public function definition(): array
    {
        return [
            'dossier_id' => Dossier::factory(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['appel', 'email', 'courrier', 'reunion', 'note']),
            'objet' => $this->faker->sentence(4),
            'contenu' => $this->faker->paragraph(),
            'date_communication' => now(),
        ];
    }
}
