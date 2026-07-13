<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReinitialiserMotDePasseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $nom, public string $token, public string $email)
    {
    }

    public function build()
    {
        $nomCabinet = CabinetSetting::instance()->nom;
        $urlFrontend = env('FRONTEND_URL', 'http://localhost:4200');
        $lien = "{$urlFrontend}/reinitialiser-mot-de-passe?email=" . urlencode($this->email) . "&token={$this->token}";

        return $this
            ->subject("Réinitialisation de votre mot de passe — {$nomCabinet}")
            ->view('emails.reinitialiser-mot-de-passe')
            ->with(['nom' => $this->nom, 'lien' => $lien]);
    }
}
