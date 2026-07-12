<?php

namespace App\Mail;

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
        return $this
            ->subject('Confirmation de votre demande de consultation — Lambert & Associés')
            ->view('emails.confirmation-rendezvous')
            ->with(['rendezVous' => $this->rendezVous]);
    }
}
