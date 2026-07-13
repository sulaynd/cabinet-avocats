<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'dossier_id' => Dossier::factory(),
            'nom_original' => $this->faker->word() . '.pdf',
            'chemin' => 'dossiers/1/' . $this->faker->uuid() . '.pdf',
            'type' => $this->faker->randomElement(['contrat', 'piece_procedure', 'correspondance', 'autre']),
            'taille' => $this->faker->numberBetween(1000, 500000),
            'uploaded_by' => User::factory(),
            'necessite_signature' => false,
        ];
    }

    public function necessiteSignature(): static
    {
        return $this->state(fn () => ['necessite_signature' => true]);
    }

    public function signe(): static
    {
        return $this->state(fn () => [
            'necessite_signature' => true,
            'signe_le' => now(),
            'signature_nom' => $this->faker->name(),
            'signature_ip' => $this->faker->ipv4(),
        ]);
    }
}
