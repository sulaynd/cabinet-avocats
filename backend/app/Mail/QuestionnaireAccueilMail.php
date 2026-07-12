<?php

namespace App\Mail;

use App\Models\ReponseQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuestionnaireAccueilMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ReponseQuestionnaire $reponse)
    {
    }

    public function build()
    {
        $lienPublic = config('app.frontend_url', config('app.url')) . '/questionnaire/' . $this->reponse->token;

        return $this
            ->subject("Avant votre rendez-vous — quelques informations, {$this->reponse->dossier->client->nom_complet}")
            ->view('emails.questionnaire-accueil')
            ->with(['reponse' => $this->reponse, 'lienPublic' => $lienPublic]);
    }
}
