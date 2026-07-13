<?php

namespace Tests\Feature;

use App\Models\Communication;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_liste_les_communications_dun_dossier_assigne(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        Communication::factory()->count(2)->create(['dossier_id' => $dossier->id]);

        $this->actingAs($avocat, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/communications")
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_refuse_laccess_a_un_dossier_non_assigne(): void
    {
        $avocat = User::factory()->avocat()->create();
        $autreAvocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $autreAvocat->id]);

        $this->actingAs($avocat, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/communications")
            ->assertStatus(403);
    }

    public function test_cree_une_communication(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/communications", [
            'type' => 'appel',
            'objet' => 'Appel de suivi',
            'contenu' => 'Discussion sur les prochaines étapes.',
        ])->assertCreated();

        $this->assertDatabaseHas('communications', ['objet' => 'Appel de suivi']);
    }

    public function test_type_invalide_refuse(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/communications", [
            'type' => 'type_invalide',
            'objet' => 'Test',
        ])->assertStatus(422);
    }

    public function test_supprime_une_communication(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $communication = Communication::factory()->create(['dossier_id' => $dossier->id]);

        $this->actingAs($avocat, 'sanctum')
            ->deleteJson("/api/communications/{$communication->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('communications', ['id' => $communication->id]);
    }
}
