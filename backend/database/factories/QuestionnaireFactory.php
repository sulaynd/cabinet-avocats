<?php

namespace Database\Factories;

use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionnaireFactory extends Factory
{
    protected $model = Questionnaire::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type_affaire' => null,
            'champs' => [
                ['cle' => 'nom', 'label' => 'Nom complet', 'type' => 'texte', 'requis' => true],
                ['cle' => 'situation', 'label' => 'Décrivez votre situation', 'type' => 'zone_texte', 'requis' => true],
            ],
            'actif' => true,
        ];
    }
}
