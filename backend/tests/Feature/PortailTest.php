<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\Facture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortailTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_ne_voit_que_ses_propres_dossiers(): void
    {
        $client = Client::factory()->create(['password' => Hash::make('motdepasse123'), 'portail_active_le' => now()]);
        $autreClient = Client::factory()->create();
        Dossier::factory()->create(['client_id' => $client->id]);
        Dossier::factory()->create(['client_id' => $autreClient->id]);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/portail/mes-dossiers')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_client_ne_peut_pas_acceder_au_dossier_dun_autre_client(): void
    {
        $client = Client::factory()->create(['password' => Hash::make('motdepasse123'), 'portail_active_le' => now()]);
        $autreClient = Client::factory()->create();
        $dossier = Dossier::factory()->create(['client_id' => $autreClient->id]);

        $this->actingAs($client, 'sanctum')
            ->getJson("/api/portail/dossiers/{$dossier->id}")
            ->assertNotFound();
    }

    public function test_client_voit_ses_factures(): void
    {
        $client = Client::factory()->create(['password' => Hash::make('motdepasse123'), 'portail_active_le' => now()]);
        $dossier = Dossier::factory()->create(['client_id' => $client->id]);
        Facture::factory()->create(['dossier_id' => $dossier->id, 'client_id' => $client->id]);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/portail/mes-factures')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_client_ne_peut_pas_telecharger_un_document_dun_autre_client(): void
    {
        $client = Client::factory()->create(['password' => Hash::make('motdepasse123'), 'portail_active_le' => now()]);
        $autreClient = Client::factory()->create();
        $dossier = Dossier::factory()->create(['client_id' => $autreClient->id]);
        $document = Document::factory()->create(['dossier_id' => $dossier->id]);

        $this->actingAs($client, 'sanctum')
            ->get("/api/portail/documents/{$document->id}/telecharger")
            ->assertStatus(403);
    }

    public function test_client_peut_signer_un_document_qui_le_necessite(): void
    {
        $client = Client::factory()->create(['password' => Hash::make('motdepasse123'), 'portail_active_le' => now()]);
        $dossier = Dossier::factory()->create(['client_id' => $client->id]);
        $document = Document::factory()->necessiteSignature()->create(['dossier_id' => $dossier->id]);

        $this->actingAs($client, 'sanctum')
            ->postJson("/api/portail/documents/{$document->id}/signer", ['nom_signataire' => 'Jean Client'])
            ->assertOk();

        $this->assertTrue($document->fresh()->estSigne());
    }
}
