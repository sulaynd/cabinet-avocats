<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FactureMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Facture $facture)
    {
    }

    public function build()
    {
        $pdf = Pdf::loadView('pdf.facture', ['facture' => $this->facture->load(['lignes', 'client', 'dossier'])]);
        $nomCabinet = CabinetSetting::instance()->nom;

        return $this
            ->subject("Facture {$this->facture->numero} — {$nomCabinet}")
            ->view('emails.facture')
            ->with(['facture' => $this->facture])
            ->attachData($pdf->output(), "{$this->facture->numero}.pdf", ['mime' => 'application/pdf']);
    }
}
