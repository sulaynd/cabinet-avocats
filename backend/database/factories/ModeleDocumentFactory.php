<?php

namespace Database\Factories;

use App\Models\ModeleDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModeleDocumentFactory extends Factory
{
    protected $model = ModeleDocument::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type_affaire' => null,
            'fichier_chemin' => 'modeles-documents/' . $this->faker->uuid() . '.docx',
            'nom_original' => $this->faker->word() . '.docx',
        ];
    }
}
