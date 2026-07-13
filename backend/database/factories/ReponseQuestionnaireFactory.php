<?php

namespace Database\Factories;

use App\Models\Dossier;
use App\Models\Questionnaire;
use App\Models\ReponseQuestionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReponseQuestionnaireFactory extends Factory
{
    protected $model = ReponseQuestionnaire::class;

    public function definition(): array
    {
        return [
            'dossier_id' => Dossier::factory(),
            'questionnaire_id' => Questionnaire::factory(),
            'token' => Str::random(32),
            'reponses' => null,
            'envoye_le' => now(),
            'rempli_le' => null,
        ];
    }

    public function rempli(): static
    {
        return $this->state(fn () => [
            'reponses' => ['nom' => 'Jean Test', 'situation' => 'Ma situation en quelques mots.'],
            'rempli_le' => now(),
        ]);
    }
}
