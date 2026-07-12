<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_connexion_reussie_avec_bons_identifiants(): void
    {
        $user = User::factory()->create(['password' => 'motdepasse123']);

        $reponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'motdepasse123',
        ]);

        $reponse->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_connexion_refusee_avec_mauvais_mot_de_passe(): void
    {
        $user = User::factory()->create(['password' => 'motdepasse123']);

        $reponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'mauvais',
        ]);

        $reponse->assertStatus(401);
    }

    public function test_doit_changer_mot_de_passe_apparait_dans_la_reponse_de_connexion(): void
    {
        $user = User::factory()->create(['password' => 'temporaire123', 'doit_changer_mot_de_passe' => true]);

        $reponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'temporaire123',
        ]);

        $reponse->assertOk()->assertJsonPath('user.doit_changer_mot_de_passe', true);
    }

    public function test_changement_de_mot_de_passe_reinitialise_le_drapeau(): void
    {
        $user = User::factory()->create(['doit_changer_mot_de_passe' => true]);

        $reponse = $this->actingAs($user, 'sanctum')->postJson('/api/changer-mot-de-passe', [
            'password' => 'nouveauMotDePasse123',
        ]);

        $reponse->assertOk();
        $this->assertFalse($user->fresh()->doit_changer_mot_de_passe);
    }

    public function test_changement_de_mot_de_passe_refuse_si_trop_court(): void
    {
        $user = User::factory()->create();

        $reponse = $this->actingAs($user, 'sanctum')->postJson('/api/changer-mot-de-passe', [
            'password' => 'court',
        ]);

        $reponse->assertStatus(422);
    }
}
