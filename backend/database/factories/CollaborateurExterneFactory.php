<?php

namespace Database\Factories;

use App\Models\CollaborateurExterne;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CollaborateurExterneFactory extends Factory
{
    protected $model = CollaborateurExterne::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'organisation' => $this->faker->company(),
            'telephone' => $this->faker->phoneNumber(),
            'password' => null,
            'portail_active_le' => null,
            'doit_changer_mot_de_passe' => true,
        ];
    }

    /** Collaborateur avec un accès portail déjà activé, mot de passe connu. */
    public function avecAcces(string $motDePasse = 'MotDePasse123'): static
    {
        return $this->state([
            'password' => Hash::make($motDePasse),
            'portail_active_le' => now(),
            'doit_changer_mot_de_passe' => false,
        ]);
    }
}
