<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\CollaborateurExterne;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CollaborateurActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CollaborateurExterne $collaborateur, public string $motDePasse)
    {
    }

    public function build()
    {
        $nomCabinet = CabinetSetting::instance()->nom;
        $urlPortail = rtrim(config('app.frontend_url', config('app.url')), '/') . '/collaborateur/connexion';

        return $this
            ->subject("Votre accès collaborateur — {$nomCabinet}")
            ->view('emails.collaborateur-activation')
            ->with([
                'collaborateur' => $this->collaborateur,
                'motDePasse' => $this->motDePasse,
                'urlPortail' => $urlPortail,
            ]);
    }
}
