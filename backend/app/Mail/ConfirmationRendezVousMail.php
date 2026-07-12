<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\RendezVousEnLigne;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmationRendezVousMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RendezVousEnLigne $rendezVous)
    {
    }

    public function build()
    {
        $nomCabinet = CabinetSetting::instance()->nom;

        return $this
            ->subject("Votre demande de rendez-vous a été reçue — {$nomCabinet}")
            ->view('emails.confirmation-rendezvous')
            ->with(['rendezVous' => $this->rendezVous]);
    }
}
