<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FactureEnRetardMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Facture $facture)
    {
    }

    public function build()
    {
        $nomCabinet = CabinetSetting::instance()->nom;

        return $this
            ->subject("Facture {$this->facture->numero} en retard de paiement — {$nomCabinet}")
            ->view('emails.facture-en-retard-cabinet')
            ->with(['facture' => $this->facture]);
    }
}
