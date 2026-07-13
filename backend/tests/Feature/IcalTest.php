<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\Echeance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IcalTest extends TestCase
{
    use RefreshDatabase;

    public function test_recupere_ses_liens_ical(): void
    {
        $avocat = User::factory()->avocat()->create();

        $reponse = $this->actingAs($avocat, 'sanctum')->getJson('/api/ical/mes-liens')->assertOk();

        $reponse->assertJsonStructure(['personnel']);
        $this->assertArrayNotHasKey('equipe', $reponse->json());
    }

    public function test_admin_recoit_aussi_le_lien_equipe(): void
    {
        $admin = User::factory()->admin()->create();

        $reponse = $this->actingAs($admin, 'sanctum')->getJson('/api/ical/mes-liens')->assertOk();

        $reponse->assertJsonStructure(['personnel', 'equipe']);
    }

    public function test_flux_personnel_valide_renvoie_un_calendrier(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        Echeance::factory()->create(['dossier_id' => $dossier->id, 'titre' => 'Audience test']);
        $avocat->regenererTokenIcal();

        $reponse = $this->get("/api/ical/perso/{$avocat->ical_token}.ics");

        $reponse->assertOk();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $reponse->content());
        $this->assertStringContainsString('Audience test', $reponse->content());
    }

    public function test_jeton_personnel_invalide_renvoie_404(): void
    {
        $this->get('/api/ical/perso/jeton-inexistant.ics')->assertNotFound();
    }

    public function test_regenerer_change_le_jeton_personnel(): void
    {
        $avocat = User::factory()->avocat()->create();
        $ancienToken = $avocat->ical_token;

        $this->actingAs($avocat, 'sanctum')->postJson('/api/ical/regenerer-personnel')->assertOk();

        $this->assertNotEquals($ancienToken, $avocat->fresh()->ical_token);
    }
}
