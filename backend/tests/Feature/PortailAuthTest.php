<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PortailAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_genere_un_mot_de_passe_et_envoie_un_email(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/clients/{$client->id}/activer-portail")
            ->assertOk();

        $client->refresh();
        $this->assertNotNull($client->portail_active_le);
        $this->assertTrue($client->doit_changer_mot_de_passe);
        Mail::assertSent(\App\Mail\PortailActivationMail::class);
    }

    public function test_activation_refusee_sans_email(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create(['email' => null]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/clients/{$client->id}/activer-portail")
            ->assertStatus(422);
    }

    public function test_connexion_client_reussie(): void
    {
        $client = Client::factory()->create([
            'password' => Hash::make('motdepasse123'),
            'portail_active_le' => now(),
        ]);

        $this->postJson('/api/portail/connexion', [
            'email' => $client->email,
            'password' => 'motdepasse123',
        ])->assertOk()->assertJsonStructure(['client', 'token']);
    }

    public function test_connexion_client_refusee_sans_activation(): void
    {
        $client = Client::factory()->create([
            'password' => Hash::make('motdepasse123'),
            'portail_active_le' => null,
        ]);

        $this->postJson('/api/portail/connexion', [
            'email' => $client->email,
            'password' => 'motdepasse123',
        ])->assertStatus(401);
    }

    public function test_email_client_unique_en_base(): void
    {
        Client::factory()->create(['email' => 'unique@test.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Client::factory()->create(['email' => 'unique@test.com']);
    }
}
