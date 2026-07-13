<?php

namespace App\Console\Commands;

use App\Mail\EcheanceRappelMail;
use App\Models\Echeance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnvoyerRappelsEcheances extends Command
{
    protected $signature = 'echeances:envoyer-rappels';

    protected $description = "Envoie un rappel par email à l'avocat et l'assistant du dossier pour chaque échéance à venir dont le délai de rappel configuré est atteint";

    public function handle(): int
    {
        // Chaque échéance a son propre délai de rappel (rappel_avant, en minutes,
        // configurable dans le formulaire) — on ne traite ici que celles pour
        // lesquelles ce délai est écoulé, jamais déjà notifiées, et toujours
        // "à venir" (pas déjà passées, annulées ou réalisées).
        $echeances = Echeance::with('dossier.avocat', 'dossier.assistant')
            ->whereNotNull('rappel_avant')
            ->where('rappel_envoye', false)
            ->where('statut', 'a_venir')
            ->get()
            ->filter(fn (Echeance $echeance) => now()->addMinutes($echeance->rappel_avant)->gte($echeance->date_heure));

        $envoyes = 0;

        foreach ($echeances as $echeance) {
            $destinataires = collect([$echeance->dossier->avocat, $echeance->dossier->assistant])
                ->filter()
                ->pluck('email')
                ->unique();

            foreach ($destinataires as $email) {
                Mail::to($email)->send(new EcheanceRappelMail($echeance));
            }

            $echeance->update(['rappel_envoye' => true]);
            $envoyes++;
        }

        $this->info("{$envoyes} rappel(s) d'échéance envoyé(s).");

        return self::SUCCESS;
    }
}
