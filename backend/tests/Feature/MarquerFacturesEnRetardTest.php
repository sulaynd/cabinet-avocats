<?php

namespace Tests\Feature;

use App\Console\Commands\MarquerFacturesEnRetard;
use App\Models\Facture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarquerFacturesEnRetardTest extends TestCase
{
    use RefreshDatabase;

    public function test_bascule_une_facture_envoyee_dont_lecheance_est_depassee(): void
    {
        $facture = Facture::factory()->create([
            'statut' => 'envoyee',
            'date_echeance' => now()->subDays(5),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class)->assertSuccessful();

        $this->assertEquals('en_retard', $facture->fresh()->statut);
    }

    public function test_ne_touche_pas_une_facture_dont_lecheance_nest_pas_encore_depassee(): void
    {
        $facture = Facture::factory()->create([
            'statut' => 'envoyee',
            'date_echeance' => now()->addDays(10),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        $this->assertEquals('envoyee', $facture->fresh()->statut);
    }

    public function test_ne_touche_pas_une_facture_deja_payee(): void
    {
        $facture = Facture::factory()->create([
            'statut' => 'payee',
            'date_echeance' => now()->subDays(5),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        $this->assertEquals('payee', $facture->fresh()->statut);
    }

    public function test_ne_touche_pas_un_brouillon(): void
    {
        $facture = Facture::factory()->create([
            'statut' => 'brouillon',
            'date_echeance' => now()->subDays(5),
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        $this->assertEquals('brouillon', $facture->fresh()->statut);
    }

    public function test_ignore_les_factures_sans_date_echeance(): void
    {
        $facture = Facture::factory()->create([
            'statut' => 'envoyee',
            'date_echeance' => null,
        ]);

        $this->artisan(MarquerFacturesEnRetard::class);

        $this->assertEquals('envoyee', $facture->fresh()->statut);
    }
}
