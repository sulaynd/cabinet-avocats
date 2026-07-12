<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password', // casté automatiquement en hash par le modèle
            'role' => $this->faker->randomElement(['admin', 'avocat', 'assistant']),
            'phone' => $this->faker->phoneNumber(),
            'taux_horaire_defaut' => $this->faker->randomFloat(2, 100, 400),
            'doit_changer_mot_de_passe' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function avocat(): static
    {
        return $this->state(fn () => ['role' => 'avocat']);
    }

    public function assistant(): static
    {
        return $this->state(fn () => ['role' => 'assistant']);
    }
}
