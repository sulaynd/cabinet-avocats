<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\Echeance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcheanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cree_une_echeance(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/echeances', [
            'dossier_id' => $dossier->id,
            'titre' => 'Audience préliminaire',
            'type' => 'audience',
            'date_heure' => now()->addDays(10)->toDateTimeString(),
            'statut' => 'a_venir',
        ])->assertCreated();

        $this->assertDatabaseHas('echeances', ['titre' => 'Audience préliminaire']);
    }

    public function test_liste_les_echeances_dun_dossier(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create();
        Echeance::factory()->count(2)->create(['dossier_id' => $dossier->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/echeances")
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_type_invalide_refuse(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/echeances', [
            'dossier_id' => $dossier->id,
            'titre' => 'Test',
            'type' => 'type_invalide',
            'date_heure' => now()->addDay()->toDateTimeString(),
            'statut' => 'a_venir',
        ])->assertStatus(422);
    }
}
