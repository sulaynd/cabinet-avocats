<?php

namespace Tests\Feature;

use App\Console\Commands\MarquerFacturesEnRetard;
use App\Mail\FactureEnRetardClientMail;
use App\Mail\FactureEnRetardMail;
use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarquerFacturesEnRetardTest extends TestCase
{
    use RefreshDatabase;

    public function test_bascule_une_facture_envoyee_dont_lecheance_est_depassee(): void
    {
        Mail::fake();
        $facture = Facture::factory()->create([
            'statut' => 'envoyee',
            'date_echeance' => now()->subDays(5),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class)->assertSuccessful();

        $this->assertEquals('en_retard', $facture->fresh()->statut);
    }

    public function test_ne_touche_pas_une_facture_dont_lecheance_nest_pas_encore_depassee(): void
    {
        Mail::fake();
        $facture = Facture::factory()->create([
            'statut' => 'envoyee',
            'date_echeance' => now()->addDays(10),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        $this->assertEquals('envoyee', $facture->fresh()->statut);
        Mail::assertNothingSent();
    }

    public function test_ne_touche_pas_une_facture_deja_payee(): void
    {
        Mail::fake();
        $facture = Facture::factory()->create([
            'statut' => 'payee',
            'date_echeance' => now()->subDays(5),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        $this->assertEquals('payee', $facture->fresh()->statut);
    }

    public function test_ne_touche_pas_un_brouillon(): void
    {
        Mail::fake();
        $facture = Facture::factory()->create([
            'statut' => 'brouillon',
            'date_echeance' => now()->subDays(5),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        $this->assertEquals('brouillon', $facture->fresh()->statut);
    }

    public function test_ignore_les_factures_sans_date_echeance(): void
    {
        Mail::fake();
        $facture = Facture::factory()->create([
            'statut' => 'envoyee',
            'date_echeance' => null,
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        $this->assertEquals('envoyee', $facture->fresh()->statut);
    }

    public function test_envoie_un_email_au_cabinet_et_au_client(): void
    {
        Mail::fake();
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        Facture::factory()->create([
            'dossier_id' => $dossier->id,
            'client_id' => $dossier->client_id,
            'statut' => 'envoyee',
            'date_echeance' => now()->subDays(3),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        Mail::assertSent(FactureEnRetardMail::class);
        Mail::assertSent(FactureEnRetardClientMail::class);
    }

    public function test_envoie_au_cabinet_a_lavocat_et_a_lassistant_et_au_stagiaire(): void
    {
        Mail::fake();
        $avocat = User::factory()->avocat()->create();
        $assistant = User::factory()->create(['role' => 'assistant']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create([
            'avocat_id' => $avocat->id,
            'assistant_id' => $assistant->id,
            'stagiaire_id' => $stagiaire->id,
        ]);
        Facture::factory()->create([
            'dossier_id' => $dossier->id,
            'client_id' => $dossier->client_id,
            'statut' => 'envoyee',
            'date_echeance' => now()->subDays(3),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        Mail::assertSent(FactureEnRetardMail::class, 3);
    }
}
