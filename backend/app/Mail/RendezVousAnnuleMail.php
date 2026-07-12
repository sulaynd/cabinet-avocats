<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\RendezVousEnLigne;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RendezVousAnnuleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RendezVousEnLigne $rendezVous)
    {
    }

    public function build()
    {
        $nomCabinet = CabinetSetting::instance()->nom;

        return $this
            ->subject("Annulation de votre rendez-vous — {$nomCabinet}")
            ->view('emails.rendezvous-annule')
            ->with(['rendezVous' => $this->rendezVous]);
    }
}
