<?php

namespace Tests\Feature;

use App\Models\Actualite;
use App\Models\CabinetSetting;
use App\Models\OffreEmploi;
use App\Models\Temoignage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabinetSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_public_ne_renvoie_que_les_coordonnees_de_base(): void
    {
        $reponse = $this->getJson('/api/parametres-cabinet/public')->assertOk();

        $reponse->assertJsonStructure(['nom', 'adresse', 'telephone', 'email']);
    }

    public function test_seul_un_admin_peut_modifier_les_parametres(): void
    {
        $avocat = User::factory()->avocat()->create();

        $this->actingAs($avocat, 'sanctum')
            ->putJson('/api/parametres-cabinet', ['nom' => 'Nouveau nom'])
            ->assertStatus(403);
    }

    public function test_admin_peut_modifier_le_nom_du_cabinet(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/parametres-cabinet', ['nom' => 'JCA Modifié'])
            ->assertOk();

        $this->assertEquals('JCA Modifié', CabinetSetting::instance()->nom);
    }

    // --- Contenu public géré depuis l'admin ---

    public function test_offres_emploi_publiques_excluent_celles_non_publiees(): void
    {
        OffreEmploi::factory()->create(['actif' => true, 'titre' => 'Poste publié']);
        OffreEmploi::factory()->create(['actif' => false, 'titre' => 'Poste caché']);

        $reponse = $this->getJson('/api/offres-emploi/public')->assertOk();

        $titres = collect($reponse->json())->pluck('titre');
        $this->assertTrue($titres->contains('Poste publié'));
        $this->assertFalse($titres->contains('Poste caché'));
    }

    public function test_offres_emploi_expirees_disparaissent_du_public(): void
    {
        OffreEmploi::factory()->create(['actif' => true, 'date_limite' => now()->subDay()]);

        $reponse = $this->getJson('/api/offres-emploi/public')->assertOk();

        $this->assertCount(0, $reponse->json());
    }

    public function test_actualites_publiques_les_plus_recentes_dabord(): void
    {
        Actualite::factory()->create(['actif' => true, 'titre' => 'Ancienne', 'date' => now()->subMonths(2)]);
        Actualite::factory()->create(['actif' => true, 'titre' => 'Récente', 'date' => now()]);

        $reponse = $this->getJson('/api/actualites/public')->assertOk();

        $this->assertEquals('Récente', $reponse->json('0.titre'));
    }

    public function test_temoignages_publics_nincluent_que_les_approuves(): void
    {
        Temoignage::factory()->create(['actif' => true]);
        Temoignage::factory()->create(['actif' => false]);

        $reponse = $this->getJson('/api/temoignages/public')->assertOk();

        $this->assertCount(1, $reponse->json());
    }
}
