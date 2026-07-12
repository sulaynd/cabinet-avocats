<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\RendezVousEnLigne;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RendezVousConfirmeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RendezVousEnLigne $rendezVous,
        public ?float $montantConsultation = null,
        public ?string $lienRencontre = null
    ) {
    }

    public function build()
    {
        $nomCabinet = CabinetSetting::instance()->nom;

        return $this
            ->subject("Votre rendez-vous est confirmé — {$nomCabinet}")
            ->view('emails.rendezvous-confirme')
            ->with([
                'rendezVous' => $this->rendezVous,
                'montantConsultation' => $this->montantConsultation,
                'lienRencontre' => $this->lienRencontre,
            ]);
    }
}
