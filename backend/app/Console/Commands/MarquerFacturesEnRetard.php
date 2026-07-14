<?php

namespace App\Console\Commands;

use App\Models\Facture;
use Illuminate\Console\Command;

class MarquerFacturesEnRetard extends Command
{
    protected $signature = 'factures:marquer-en-retard';

    protected $description = "Bascule automatiquement vers 'en_retard' toute facture envoyée, non payée, dont la date d'échéance est dépassée";

    public function handle(): int
    {
        $nombre = Facture::where('statut', 'envoyee')
            ->whereNotNull('date_echeance')
            ->whereDate('date_echeance', '<', now())
            ->update(['statut' => 'en_retard']);

        $this->info("{$nombre} facture(s) basculée(s) en retard.");

        return self::SUCCESS;
    }
}
