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
        public ?string $lienRencontre = null,
        public ?int $dureeMinutes = 60
    ) {
    }

    /** Convertit la durée en minutes vers un libellé français lisible (30 minutes, 1h, 1h 30, 2h...). */
    private function libelleDuree(): string
    {
        $minutes = $this->dureeMinutes ?? 60;

        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $heures = intdiv($minutes, 60);
        $reste = $minutes % 60;

        return $reste === 0 ? "{$heures}h" : "{$heures}h {$reste}";
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
                'dureeLibelle' => $this->libelleDuree(),
            ]);
    }
}
