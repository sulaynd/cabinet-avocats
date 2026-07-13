<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    public function test_seul_un_admin_peut_consulter_le_tableau_de_bord(): void
    {
        $avocat = User::factory()->avocat()->create();

        $this->actingAs($avocat, 'sanctum')
            ->getJson('/api/tableau-de-bord')
            ->assertStatus(403);
    }

    public function test_le_chiffre_daffaires_ne_compte_que_les_factures_payees(): void
    {
        $admin = User::factory()->admin()->create();
        Facture::factory()->payee()->create(['montant_ttc' => 1000]);
        Facture::factory()->create(['statut' => 'brouillon', 'montant_ttc' => 500]);

        $reponse = $this->actingAs($admin, 'sanctum')->getJson('/api/tableau-de-bord')->assertOk();

        $this->assertEquals(1000, $reponse->json('ca_total'));
    }

    public function test_compte_les_factures_impayees(): void
    {
        $admin = User::factory()->admin()->create();
        Facture::factory()->envoyee()->create(['montant_ttc' => 300]);
        Facture::factory()->envoyee()->create(['montant_ttc' => 200]);

        $reponse = $this->actingAs($admin, 'sanctum')->getJson('/api/tableau-de-bord')->assertOk();

        $this->assertEquals(500, $reponse->json('factures_impayees.montant'));
        $this->assertEquals(2, $reponse->json('factures_impayees.nombre'));
    }

    public function test_retourne_la_repartition_des_dossiers_par_statut(): void
    {
        $admin = User::factory()->admin()->create();
        Dossier::factory()->create(['statut' => 'ouvert']);
        Dossier::factory()->clos()->create();

        $reponse = $this->actingAs($admin, 'sanctum')->getJson('/api/tableau-de-bord')->assertOk();

        $this->assertEquals(1, $reponse->json('dossiers_par_statut.ouvert'));
        $this->assertEquals(1, $reponse->json('dossiers_par_statut.clos'));
    }
}
