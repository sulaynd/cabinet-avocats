<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Temoignage;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemoignageFactory extends Factory
{
    protected $model = Temoignage::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'texte' => $this->faker->paragraph(),
            'ordre' => 0,
            'actif' => false,
        ];
    }
}
