<?php

namespace Tests\Feature;

use App\Console\Commands\EnvoyerRappelsEcheances;
use App\Mail\EcheanceRappelMail;
use App\Models\Dossier;
use App\Models\Echeance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EcheanceRappelTest extends TestCase
{
    use RefreshDatabase;

    public function test_envoie_un_rappel_quand_le_delai_est_atteint(): void
    {
        Mail::fake();
        $dossier = Dossier::factory()->create();
        $echeance = Echeance::factory()->create([
            'dossier_id' => $dossier->id,
            'date_heure' => now()->addMinutes(4320), // pile dans 3 jours
            'rappel_avant' => 4320,
            'rappel_envoye' => false,
            'statut' => 'a_venir',
        ]);

        $this->artisan(EnvoyerRappelsEcheances::class)->assertSuccessful();

        Mail::assertSent(EcheanceRappelMail::class);
        $this->assertTrue($echeance->fresh()->rappel_envoye);
    }

    public function test_nenvoie_rien_si_le_delai_nest_pas_encore_atteint(): void
    {
        Mail::fake();
        $dossier = Dossier::factory()->create();
        Echeance::factory()->create([
            'dossier_id' => $dossier->id,
            'date_heure' => now()->addDays(10), // bien trop loin
            'rappel_avant' => 4320,
            'rappel_envoye' => false,
            'statut' => 'a_venir',
        ]);

        $this->artisan(EnvoyerRappelsEcheances::class);

        Mail::assertNotSent(EcheanceRappelMail::class);
    }

    public function test_nenvoie_jamais_deux_fois_le_meme_rappel(): void
    {
        Mail::fake();
        $dossier = Dossier::factory()->create();
        Echeance::factory()->create([
            'dossier_id' => $dossier->id,
            'date_heure' => now()->addMinutes(4320),
            'rappel_avant' => 4320,
            'rappel_envoye' => true, // déjà envoyé précédemment
            'statut' => 'a_venir',
        ]);

        $this->artisan(EnvoyerRappelsEcheances::class);

        Mail::assertNotSent(EcheanceRappelMail::class);
    }

    public function test_aucun_rappel_si_non_configure(): void
    {
        Mail::fake();
        $dossier = Dossier::factory()->create();
        Echeance::factory()->create([
            'dossier_id' => $dossier->id,
            'date_heure' => now()->addMinutes(4320),
            'rappel_avant' => null, // "Aucun rappel"
            'rappel_envoye' => false,
            'statut' => 'a_venir',
        ]);

        $this->artisan(EnvoyerRappelsEcheances::class);

        Mail::assertNotSent(EcheanceRappelMail::class);
    }

    public function test_envoie_a_lavocat_et_a_lassistant_du_dossier(): void
    {
        Mail::fake();
        $avocat = User::factory()->avocat()->create();
        $assistant = User::factory()->assistant()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id, 'assistant_id' => $assistant->id]);
        Echeance::factory()->create([
            'dossier_id' => $dossier->id,
            'date_heure' => now()->addMinutes(4320),
            'rappel_avant' => 4320,
            'rappel_envoye' => false,
            'statut' => 'a_venir',
        ]);

        $this->artisan(EnvoyerRappelsEcheances::class);

        Mail::assertSent(EcheanceRappelMail::class, 2);
    }
}
