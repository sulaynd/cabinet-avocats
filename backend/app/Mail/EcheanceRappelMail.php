<?php

namespace App\Mail;

use App\Models\CabinetSetting;
use App\Models\Echeance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EcheanceRappelMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Echeance $echeance)
    {
    }

    public function build()
    {
        $nomCabinet = CabinetSetting::instance()->nom;

        return $this
            ->subject("Rappel — {$this->echeance->titre} à venir — {$nomCabinet}")
            ->view('emails.echeance-rappel')
            ->with(['echeance' => $this->echeance]);
    }
}
