<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FactureEnRetardClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Facture $facture)
    {
    }

    public function build()
    {
        $cabinet = CabinetSetting::instance();

        return $this
            ->subject("Rappel — Facture {$this->facture->numero} en retard de paiement — {$cabinet->nom}")
            ->view('emails.facture-en-retard-client')
            ->with(['facture' => $this->facture, 'cabinet' => $cabinet]);
    }
}
