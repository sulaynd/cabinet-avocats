<?php

namespace Tests\Feature;

use App\Mail\ReinitialiserMotDePasseMail;
use App\Mail\ReinitialiserMotDePassePortailMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MotDePasseOublieTest extends TestCase
{
    use RefreshDatabase;

    // --- Côté cabinet ---

    public function test_demande_envoie_un_email_si_le_compte_existe(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->postJson('/api/mot-de-passe-oublie', ['email' => $user->email])->assertOk();

        Mail::assertSent(ReinitialiserMotDePasseMail::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_demande_repond_pareil_meme_si_le_compte_nexiste_pas(): void
    {
        Mail::fake();

        $reponse = $this->postJson('/api/mot-de-passe-oublie', ['email' => 'inconnu@test.com']);

        $reponse->assertOk();
        Mail::assertNothingSent();
    }

    public function test_reinitialisation_reussie_avec_bon_jeton(): void
    {
        $user = User::factory()->create();
        $token = 'jeton-de-test-123';
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now()->toDateTimeString(),
        ]);

        $this->postJson('/api/reinitialiser-mot-de-passe', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'nouveauMotDePasse123',
        ])->assertOk();

        $this->assertTrue(Hash::check('nouveauMotDePasse123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reinitialisation_refusee_avec_mauvais_jeton(): void
    {
        $user = User::factory()->create();
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('bon-jeton'),
            'created_at' => now()->toDateTimeString(),
        ]);

        $this->postJson('/api/reinitialiser-mot-de-passe', [
            'email' => $user->email,
            'token' => 'mauvais-jeton',
            'password' => 'nouveauMotDePasse123',
        ])->assertStatus(422);
    }

    public function test_reinitialisation_refusee_si_jeton_expire(): void
    {
        $user = User::factory()->create();
        $token = 'jeton-expire';
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now()->subMinutes(90)->toDateTimeString(), // plus d'une heure
        ]);

        $this->postJson('/api/reinitialiser-mot-de-passe', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'nouveauMotDePasse123',
        ])->assertStatus(422);
    }

    // --- Côté portail client ---

    public function test_demande_portail_envoie_un_email_si_le_client_existe(): void
    {
        Mail::fake();
        $client = Client::factory()->create();

        $this->postJson('/api/portail/mot-de-passe-oublie', ['email' => $client->email])->assertOk();

        Mail::assertSent(ReinitialiserMotDePassePortailMail::class);
        $this->assertDatabaseHas('client_password_reset_tokens', ['email' => $client->email]);
    }

    public function test_reinitialisation_portail_reussie_avec_bon_jeton(): void
    {
        $client = Client::factory()->create();
        $token = 'jeton-client-123';
        DB::table('client_password_reset_tokens')->insert([
            'email' => $client->email,
            'token' => Hash::make($token),
            'created_at' => now()->toDateTimeString(),
        ]);

        $this->postJson('/api/portail/reinitialiser-mot-de-passe', [
            'email' => $client->email,
            'token' => $token,
            'password' => 'nouveauMotDePasse123',
        ])->assertOk();

        $this->assertTrue(Hash::check('nouveauMotDePasse123', $client->fresh()->password));
    }
}
