<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_liste_les_clients(): void
    {
        $admin = User::factory()->admin()->create();
        Client::factory()->count(2)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_creation_particulier_exige_nom(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/clients', ['type' => 'particulier', 'prenom' => 'Jean'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nom']);
    }

    public function test_creation_entreprise_exige_raison_sociale(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/clients', ['type' => 'entreprise'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['raison_sociale']);
    }

    public function test_email_doit_etre_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Client::factory()->create(['email' => 'deja@present.com']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/clients', [
                'type' => 'particulier', 'nom' => 'Dupont', 'prenom' => 'Jean',
                'email' => 'deja@present.com', 'telephone' => '5145550000',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_suppression_refusee_si_le_client_a_un_dossier(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        Dossier::factory()->create(['client_id' => $client->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/clients/{$client->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_suppression_autorisee_si_aucun_dossier(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/clients/{$client->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }
}
