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

    public function test_stagiaire_ne_peut_pas_supprimer_un_client(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $client = \App\Models\Client::factory()->create();

        $this->actingAs($stagiaire, 'sanctum')
            ->deleteJson("/api/clients/{$client->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_stagiaire_ne_peut_pas_confirmer_un_rendez_vous(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $rendezVous = \App\Models\RendezVousEnLigne::factory()->create(['statut' => 'demande']);

        $this->actingAs($stagiaire, 'sanctum')
            ->postJson("/api/rendez-vous/{$rendezVous->id}/confirmer", ['montant_consultation' => 150])
            ->assertStatus(403);
    }

    public function test_stagiaire_ne_peut_pas_annuler_un_rendez_vous(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $rendezVous = \App\Models\RendezVousEnLigne::factory()->create(['statut' => 'confirme']);

        $this->actingAs($stagiaire, 'sanctum')
            ->postJson("/api/rendez-vous/{$rendezVous->id}/annuler")
            ->assertStatus(403);
    }

    public function test_assistant_peut_toujours_confirmer_un_rendez_vous(): void
    {
        $assistant = User::factory()->create(['role' => 'assistant']);
        $rendezVous = \App\Models\RendezVousEnLigne::factory()->create(['statut' => 'demande']);

        $this->actingAs($assistant, 'sanctum')
            ->postJson("/api/rendez-vous/{$rendezVous->id}/confirmer", ['montant_consultation' => 150])
            ->assertOk();
    }

    public function test_stagiaire_ne_peut_pas_modifier_une_echeance(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id]);
        $echeance = \App\Models\Echeance::factory()->create(['dossier_id' => $dossier->id, 'statut' => 'a_venir']);

        $this->actingAs($stagiaire, 'sanctum')
            ->putJson("/api/echeances/{$echeance->id}", ['titre' => 'Modifié'])
            ->assertStatus(403);
    }

    public function test_stagiaire_ne_peut_pas_marquer_une_echeance_realisee(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id]);
        $echeance = \App\Models\Echeance::factory()->create(['dossier_id' => $dossier->id, 'statut' => 'a_venir']);

        $this->actingAs($stagiaire, 'sanctum')
            ->putJson("/api/echeances/{$echeance->id}", ['statut' => 'realisee'])
            ->assertStatus(403);
    }

    public function test_stagiaire_ne_peut_pas_supprimer_une_echeance(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id]);
        $echeance = \App\Models\Echeance::factory()->create(['dossier_id' => $dossier->id]);

        $this->actingAs($stagiaire, 'sanctum')
            ->deleteJson("/api/echeances/{$echeance->id}")
            ->assertStatus(403);
    }

    public function test_stagiaire_ne_peut_pas_supprimer_un_document(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id]);
        $document = \App\Models\Document::factory()->create(['dossier_id' => $dossier->id]);

        $this->actingAs($stagiaire, 'sanctum')
            ->deleteJson("/api/documents/{$document->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_stagiaire_ne_peut_pas_modifier_les_reglages_de_facturation(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create([
            'assistant_id' => $stagiaire->id,
            'mode_facturation' => 'horaire',
            'facturation_periodique' => false,
            'facturer_a_cloture' => false,
        ]);

        $this->actingAs($stagiaire, 'sanctum')->putJson("/api/dossiers/{$dossier->id}", [
            'mode_facturation' => 'forfait',
            'montant_forfait' => 5000,
            'facturation_periodique' => true,
            'facturer_a_cloture' => true,
        ])->assertOk();

        $dossier->refresh();
        $this->assertEquals('horaire', $dossier->mode_facturation);
        $this->assertFalse((bool) $dossier->facturation_periodique);
        $this->assertFalse((bool) $dossier->facturer_a_cloture);
    }

    public function test_date_ouverture_est_verrouillee_pour_tous_meme_ladmin(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create(['date_ouverture' => '2026-01-15']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/dossiers/{$dossier->id}", ['date_ouverture' => '2020-01-01'])
            ->assertOk();

        $this->assertEquals('2026-01-15', $dossier->fresh()->date_ouverture->format('Y-m-d'));
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
