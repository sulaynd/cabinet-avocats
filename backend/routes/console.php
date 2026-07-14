<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Envoie les rappels d'échéances (audiences, RDV client, délais...) dont le
// délai de rappel configuré est atteint, tous les jours à 8h.
Schedule::command('echeances:envoyer-rappels')->dailyAt('08:00');

// Bascule automatiquement les factures envoyées et non payées vers "en
// retard" une fois leur date d'échéance dépassée, tous les jours à 7h.
Schedule::command('factures:marquer-en-retard')->dailyAt('07:00');
