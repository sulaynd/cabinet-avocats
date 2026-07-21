<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\Intervenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntervenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_liste_les_intervenants_lies_a_un_dossier_assigne(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $intervenant = Intervenant::factory()->create();
        $dossier->intervenants()->attach($intervenant->id);

        $this->actingAs($avocat, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/intervenants")
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_refuse_laccess_a_un_dossier_non_assigne(): void
    {
        $avocat = User::factory()->avocat()->create();
        $autreAvocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $autreAvocat->id]);

        $this->actingAs($avocat, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/intervenants")
            ->assertStatus(403);
    }

    public function test_cree_un_intervenant_et_le_lie_directement_au_dossier(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/intervenants", [
            'nom' => 'Me Tremblay',
            'fonction' => 'avocat_adverse',
            'organisation' => 'Cabinet Tremblay & Associés',
            'email' => 'tremblay@example.com',
        ])->assertCreated();

        $this->assertDatabaseHas('intervenants', ['nom' => 'Me Tremblay']);
        $this->assertEquals(1, $dossier->intervenants()->count());
    }

    public function test_meme_intervenant_peut_etre_lie_a_plusieurs_dossiers(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier1 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $dossier2 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $intervenant = Intervenant::factory()->create(['nom' => 'Me Tremblay']);

        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier1->id}/intervenants/{$intervenant->id}/lier")
            ->assertCreated();
        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier2->id}/intervenants/{$intervenant->id}/lier")
            ->assertCreated();

        $this->assertEquals(1, Intervenant::where('nom', 'Me Tremblay')->count());
        $this->assertEquals(1, $dossier1->intervenants()->count());
        $this->assertEquals(1, $dossier2->intervenants()->count());
    }

    public function test_delier_ne_supprime_pas_lintervenant_du_repertoire(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier1 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $dossier2 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $intervenant = Intervenant::factory()->create();
        $dossier1->intervenants()->attach($intervenant->id);
        $dossier2->intervenants()->attach($intervenant->id);

        $this->actingAs($avocat, 'sanctum')
            ->deleteJson("/api/dossiers/{$dossier1->id}/intervenants/{$intervenant->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('intervenants', ['id' => $intervenant->id]);
        $this->assertEquals(0, $dossier1->intervenants()->count());
        $this->assertEquals(1, $dossier2->intervenants()->count());
    }

    public function test_fonction_invalide_refusee(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/intervenants", [
            'nom' => 'Test',
            'fonction' => 'fonction_invalide',
        ])->assertStatus(422);
    }

    public function test_modifie_un_intervenant_du_repertoire(): void
    {
        $avocat = User::factory()->avocat()->create();
        $intervenant = Intervenant::factory()->create(['nom' => 'Ancien nom']);

        $this->actingAs($avocat, 'sanctum')
            ->putJson("/api/intervenants/{$intervenant->id}", ['nom' => 'Nouveau nom', 'fonction' => 'expert'])
            ->assertOk();

        $this->assertEquals('Nouveau nom', $intervenant->fresh()->nom);
    }

    public function test_supprime_definitivement_un_intervenant_du_repertoire(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $intervenant = Intervenant::factory()->create();
        $dossier->intervenants()->attach($intervenant->id);

        $this->actingAs($avocat, 'sanctum')
            ->deleteJson("/api/intervenants/{$intervenant->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('intervenants', ['id' => $intervenant->id]);
    }

    public function test_recherche_dans_le_repertoire(): void
    {
        $avocat = User::factory()->avocat()->create();
        Intervenant::factory()->create(['nom' => 'Me Tremblay', 'organisation' => 'Cabinet A']);
        Intervenant::factory()->create(['nom' => 'Me Gagnon', 'organisation' => 'Cabinet B']);

        $reponse = $this->actingAs($avocat, 'sanctum')
            ->getJson('/api/intervenants?recherche=Tremblay')
            ->assertOk();

        $this->assertCount(1, $reponse->json());
    }
}
