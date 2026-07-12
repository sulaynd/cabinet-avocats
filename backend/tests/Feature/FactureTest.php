<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactureTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_calcule_correctement_tps_et_tvq(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create();

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/factures', [
            'dossier_id' => $dossier->id,
            'client_id' => $dossier->client_id,
            'date_emission' => now()->toDateString(),
            'taux_tps' => 5,
            'taux_tvq' => 9.975,
            'lignes' => [
                ['description' => 'Consultation', 'quantite' => 1, 'prix_unitaire' => 1000],
            ],
        ]);

        $reponse->assertCreated();
        $facture = Facture::first();

        $this->assertEquals(1000, $facture->montant_ht);
        $this->assertEquals(50, $facture->montant_tps);
        $this->assertEquals(99.75, $facture->montant_tvq);
        $this->assertEquals(1149.75, $facture->montant_ttc);
    }

    public function test_numerotation_se_base_sur_le_maximum_pas_sur_le_compte(): void
    {
        // Reproduit le bug historique : une facture au milieu de la séquence
        // supprimée ne doit jamais faire regénérer un numéro déjà utilisé.
        $admin = User::factory()->admin()->create();
        $annee = now()->year;

        Facture::factory()->create(['numero' => "FAC-{$annee}-0001"]);
        Facture::factory()->create(['numero' => "FAC-{$annee}-0002"]);
        $facture6 = Facture::factory()->create(['numero' => "FAC-{$annee}-0006"]);

        $dossier = Dossier::factory()->create();

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/factures', [
            'dossier_id' => $dossier->id,
            'client_id' => $dossier->client_id,
            'date_emission' => now()->toDateString(),
            'lignes' => [['description' => 'Test', 'quantite' => 1, 'prix_unitaire' => 100]],
        ]);

        $reponse->assertCreated();
        $this->assertEquals("FAC-{$annee}-0007", $reponse->json('numero'));
    }

    public function test_suppression_refusee_pour_une_facture_payee(): void
    {
        $admin = User::factory()->admin()->create();
        $facture = Facture::factory()->payee()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/factures/{$facture->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('factures', ['id' => $facture->id]);
    }

    public function test_suppression_autorisee_pour_un_brouillon(): void
    {
        $admin = User::factory()->admin()->create();
        $facture = Facture::factory()->create(['statut' => 'brouillon']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/factures/{$facture->id}")
            ->assertNoContent();
    }
}
