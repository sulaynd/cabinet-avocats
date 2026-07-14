<?php

namespace App\Console\Commands;

use App\Mail\FactureEnRetardClientMail;
use App\Mail\FactureEnRetardMail;
use App\Models\Facture;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MarquerFacturesEnRetard extends Command
{
    protected $signature = 'factures:marquer-en-retard';

    protected $description = "Bascule automatiquement vers 'en_retard' toute facture envoyée, non payée, dont la date d'échéance est dépassée, et avertit le cabinet et le client par email";

    public function handle(): int
    {
        $factures = Facture::with('client', 'dossier.avocat', 'dossier.assistant', 'dossier.stagiaire')
            ->where('statut', 'envoyee')
            ->whereNotNull('date_echeance')
            ->whereDate('date_echeance', '<', now())
            ->get();

        foreach ($factures as $facture) {
            $facture->update(['statut' => 'en_retard']);

            $destinatairesCabinet = collect([$facture->dossier->avocat, $facture->dossier->assistant, $facture->dossier->stagiaire])
                ->filter()
                ->pluck('email')
                ->unique();

            foreach ($destinatairesCabinet as $email) {
                Mail::to($email)->send(new FactureEnRetardMail($facture));
            }

            if ($facture->client->email) {
                Mail::to($facture->client->email)->send(new FactureEnRetardClientMail($facture));
            }
        }

        $this->info("{$factures->count()} facture(s) basculée(s) en retard.");

        return self::SUCCESS;
    }
}
