<?php

namespace Tests\Feature;

use App\Models\Debourse;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_liste_les_debourses_dun_dossier_assigne(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        Debourse::factory()->count(2)->create(['dossier_id' => $dossier->id]);

        $this->actingAs($avocat, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/debourses")
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_refuse_laccess_a_un_dossier_non_assigne(): void
    {
        $avocat = User::factory()->avocat()->create();
        $autreAvocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $autreAvocat->id]);

        $this->actingAs($avocat, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/debourses")
            ->assertStatus(403);
    }

    public function test_cree_un_debourse(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/debourses", [
            'categorie' => 'frais_cour',
            'description' => 'Frais de dépôt',
            'montant' => 150,
            'date_debourse' => now()->toDateString(),
        ])->assertCreated();

        $this->assertDatabaseHas('debourses', ['description' => 'Frais de dépôt', 'dossier_id' => $dossier->id]);
    }

    public function test_stagiaire_ne_peut_pas_creer_de_debourse(): void
    {
        $stagiaire = User::factory()->create(['role' => 'stagiaire']);
        $dossier = Dossier::factory()->create(['assistant_id' => $stagiaire->id]);

        $this->actingAs($stagiaire, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/debourses", [
            'categorie' => 'frais_cour',
            'description' => 'Test',
            'montant' => 50,
            'date_debourse' => now()->toDateString(),
        ])->assertStatus(403);
    }

    public function test_refuse_lajout_sur_un_dossier_clos(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->clos()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/debourses", [
            'categorie' => 'frais_cour',
            'description' => 'Test',
            'montant' => 50,
            'date_debourse' => now()->toDateString(),
        ])->assertStatus(422);
    }

    public function test_ne_peut_pas_modifier_un_debourse_deja_facture(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $facture = \App\Models\Facture::factory()->create(['dossier_id' => $dossier->id, 'client_id' => $dossier->client_id]);
        $debourse = Debourse::factory()->create(['dossier_id' => $dossier->id, 'facture_id' => $facture->id]);

        $this->actingAs($avocat, 'sanctum')
            ->putJson("/api/debourses/{$debourse->id}", ['categorie' => 'autre', 'description' => 'Modifié', 'montant' => 99, 'date_debourse' => now()->toDateString()])
            ->assertStatus(422);
    }

    public function test_supprime_un_debourse_non_facture(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $debourse = Debourse::factory()->create(['dossier_id' => $dossier->id]);

        $this->actingAs($avocat, 'sanctum')
            ->deleteJson("/api/debourses/{$debourse->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('debourses', ['id' => $debourse->id]);
    }

    public function test_generation_de_facture_inclut_les_debourses_non_factures(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id, 'mode_facturation' => 'horaire']);
        \App\Models\TempsPasse::factory()->create(['dossier_id' => $dossier->id, 'user_id' => $avocat->id, 'facturable' => true, 'facture_id' => null]);
        $debourse = Debourse::factory()->create(['dossier_id' => $dossier->id, 'montant' => 75, 'description' => 'Frais de cour']);

        $reponse = $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier->id}/factures/generer-depuis-temps")
            ->assertCreated();

        $lignes = collect($reponse->json('lignes'));
        $this->assertTrue($lignes->contains(fn ($l) => str_contains($l['description'], 'Frais de cour')));
        $this->assertEquals($dossier->id, $debourse->fresh()->dossier_id);
        $this->assertNotNull($debourse->fresh()->facture_id);
    }

    public function test_generation_de_facture_forfait_inclut_aussi_les_debourses(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id, 'mode_facturation' => 'forfait', 'montant_forfait' => 2000]);
        Debourse::factory()->create(['dossier_id' => $dossier->id, 'montant' => 40, 'description' => 'Photocopies']);

        $reponse = $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier->id}/factures/generer-depuis-temps")
            ->assertCreated();

        $lignes = collect($reponse->json('lignes'));
        $this->assertTrue($lignes->contains(fn ($l) => str_contains($l['description'], 'Photocopies')));
    }

    public function test_debourse_deja_facture_nest_pas_refacture(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id, 'mode_facturation' => 'horaire']);
        \App\Models\TempsPasse::factory()->create(['dossier_id' => $dossier->id, 'user_id' => $avocat->id, 'facturable' => true, 'facture_id' => null]);
        $ancienneFacture = \App\Models\Facture::factory()->create(['dossier_id' => $dossier->id, 'client_id' => $dossier->client_id]);
        Debourse::factory()->create(['dossier_id' => $dossier->id, 'facture_id' => $ancienneFacture->id, 'description' => 'Déjà facturé']);

        $reponse = $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier->id}/factures/generer-depuis-temps")
            ->assertCreated();

        $lignes = collect($reponse->json('lignes'));
        $this->assertFalse($lignes->contains(fn ($l) => str_contains($l['description'], 'Déjà facturé')));
    }
}
