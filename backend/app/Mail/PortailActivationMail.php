<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PortailActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Client $client, public string $motDePasse)
    {
    }

    public function build()
    {
        $nomCabinet = CabinetSetting::instance()->nom;
        $urlPortail = rtrim(config('app.frontend_url', config('app.url')), '/') . '/portail/connexion';

        return $this
            ->subject("Votre accès à l'espace client — {$nomCabinet}")
            ->view('emails.portail-activation')
            ->with([
                'client' => $this->client,
                'motDePasse' => $this->motDePasse,
                'urlPortail' => $urlPortail,
            ]);
    }
}
