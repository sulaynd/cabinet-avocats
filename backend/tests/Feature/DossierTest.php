<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DossierTest extends TestCase
{
    use RefreshDatabase;

    public function test_liste_les_dossiers(): void
    {
        $admin = User::factory()->admin()->create();
        Dossier::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/dossiers')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_cree_un_dossier(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        $avocat = User::factory()->avocat()->create();

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/dossiers', [
            'client_id' => $client->id,
            'avocat_id' => $avocat->id,
            'titre' => 'Litige commercial',
            'type_affaire' => 'conseils_strategiques',
            'statut' => 'ouvert',
            'mode_facturation' => 'horaire',
        ]);

        $reponse->assertCreated();
        $this->assertDatabaseHas('dossiers', ['titre' => 'Litige commercial']);
    }

    public function test_date_cloture_se_remplit_automatiquement_a_la_fermeture(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create(['statut' => 'ouvert', 'date_cloture' => null]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/dossiers/{$dossier->id}", ['statut' => 'clos'])
            ->assertOk();

        $this->assertNotNull($dossier->fresh()->date_cloture);
    }

    public function test_date_cloture_se_vide_a_la_reouverture(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->clos()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/dossiers/{$dossier->id}", ['statut' => 'ouvert'])
            ->assertOk();

        $this->assertNull($dossier->fresh()->date_cloture);
    }

    public function test_suppression_refusee_pour_un_dossier_clos(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->clos()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/dossiers/{$dossier->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('dossiers', ['id' => $dossier->id]);
    }

    public function test_suppression_refusee_pour_un_dossier_archive(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create(['statut' => 'archive']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/dossiers/{$dossier->id}")
            ->assertStatus(422);
    }

    public function test_suppression_autorisee_pour_un_dossier_ouvert(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create(['statut' => 'ouvert']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/dossiers/{$dossier->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('dossiers', ['id' => $dossier->id]);
    }

    public function test_ajout_echeance_refuse_sur_dossier_clos(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->clos()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/echeances', [
                'dossier_id' => $dossier->id,
                'titre' => 'Audience',
                'type' => 'audience',
                'date_heure' => now()->addDays(5)->toDateTimeString(),
                'statut' => 'a_venir',
            ])
            ->assertStatus(422);
    }
}
