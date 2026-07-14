<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StagiairePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stagiaire_ne_peut_pas_cloturer_un_dossier(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id, 'statut' => 'ouvert']);

        $this->actingAs($stagiaire, 'sanctum')
            ->putJson("/api/dossiers/{$dossier->id}", ['statut' => 'clos'])
            ->assertStatus(403);

        $this->assertEquals('ouvert', $dossier->fresh()->statut);
    }

    public function test_stagiaire_peut_modifier_un_dossier_sans_le_cloturer(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id, 'statut' => 'ouvert']);

        $this->actingAs($stagiaire, 'sanctum')
            ->putJson("/api/dossiers/{$dossier->id}", ['titre' => 'Nouveau titre'])
            ->assertOk();

        $this->assertEquals('Nouveau titre', $dossier->fresh()->titre);
    }

    public function test_stagiaire_ne_peut_pas_creer_une_facture(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id]);

        $this->actingAs($stagiaire, 'sanctum')->postJson('/api/factures', [
            'dossier_id' => $dossier->id,
            'client_id' => $dossier->client_id,
            'date_emission' => now()->toDateString(),
            'lignes' => [['description' => 'Test', 'quantite' => 1, 'prix_unitaire' => 100]],
        ])->assertStatus(403);
    }

    public function test_stagiaire_peut_voir_les_factures(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id]);
        Facture::factory()->create(['dossier_id' => $dossier->id, 'client_id' => $dossier->client_id]);

        $this->actingAs($stagiaire, 'sanctum')
            ->getJson('/api/factures')
            ->assertOk();
    }

    public function test_stagiaire_ne_peut_pas_acceder_aux_communications(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id]);

        $this->actingAs($stagiaire, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/communications")
            ->assertStatus(403);
    }

    public function test_assistant_garde_un_acces_complet_aux_dossiers_et_communications(): void
    {
        $assistant = User::factory()->create(['role' => 'assistant']);
        $dossier = Dossier::factory()->create(['assistant_id' => $assistant->id, 'statut' => 'ouvert']);

        $this->actingAs($assistant, 'sanctum')
            ->putJson("/api/dossiers/{$dossier->id}", ['statut' => 'clos'])
            ->assertOk();

        $this->actingAs($assistant, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/communications")
            ->assertOk();
    }

    public function test_peut_assigner_un_assistant_et_un_stagiaire_simultanement(): void
    {
        $admin = User::factory()->admin()->create();
        $avocat = User::factory()->avocat()->create();
        $assistant = User::factory()->create(['role' => 'assistant']);
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/assigner", [
            'avocat_id' => $avocat->id,
            'assistant_id' => $assistant->id,
            'stagiaire_id' => $stagiaire->id,
        ])->assertOk();

        $dossier->refresh();
        $this->assertEquals($assistant->id, $dossier->assistant_id);
        $this->assertEquals($stagiaire->id, $dossier->stagiaire_id);

        // Les deux doivent alors avoir accès au dossier, chacun avec ses
        // propres permissions (le stagiaire reste soumis à ses restrictions).
        $this->actingAs($assistant, 'sanctum')->getJson("/api/dossiers/{$dossier->id}")->assertOk();
        $this->actingAs($stagiaire, 'sanctum')->getJson("/api/dossiers/{$dossier->id}")->assertOk();
    }
}
