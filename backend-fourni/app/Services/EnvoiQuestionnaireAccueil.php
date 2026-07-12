<?php

namespace App\Services;

use App\Mail\QuestionnaireAccueilMail;
use App\Models\Dossier;
use App\Models\Questionnaire;
use App\Models\ReponseQuestionnaire;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Onboarding client : dès l'ouverture d'un dossier, envoie automatiquement au
 * client le questionnaire de pré-consultation applicable à son type d'affaire
 * (s'il en existe un actif), via un lien public sécurisé par jeton.
 */
class EnvoiQuestionnaireAccueil
{
    public static function pourDossier(Dossier $dossier): ?ReponseQuestionnaire
    {
        $questionnaire = Questionnaire::pourTypeAffaire($dossier->type_affaire);

        if (! $questionnaire || ! $dossier->client->email) {
            return null;
        }

        $reponse = ReponseQuestionnaire::create([
            'dossier_id' => $dossier->id,
            'questionnaire_id' => $questionnaire->id,
            'token' => Str::random(48),
            'envoye_le' => now(),
        ]);

        Mail::to($dossier->client->email)->send(new QuestionnaireAccueilMail($reponse->load('dossier', 'questionnaire')));

        return $reponse;
    }
}
