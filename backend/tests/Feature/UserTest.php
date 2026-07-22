<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_tout_membre_du_personnel_peut_lister_les_utilisateurs(): void
    {
        // Nécessaire pour les menus déroulants (avocat/assistant/stagiaire
        // responsable) dans le formulaire de dossier, accessible à tout rôle.
        $avocat = User::factory()->avocat()->create();

        $this->actingAs($avocat, 'sanctum')
            ->getJson('/api/users')
            ->assertStatus(200);
    }

    public function test_seul_un_admin_peut_creer_un_utilisateur(): void
    {
        $avocat = User::factory()->avocat()->create();

        $this->actingAs($avocat, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Test',
                'email' => 'test-non-admin@example.com',
                'password' => 'password123',
                'role' => 'avocat',
            ])
            ->assertStatus(403);
    }

    public function test_admin_peut_creer_un_membre(): void
    {
        $admin = User::factory()->admin()->create();

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/users', [
            'name' => 'Malick Ndiaye',
            'email' => 'malick@jca.ca',
            'password' => 'motdepasse123',
            'role' => 'avocat',
        ]);

        $reponse->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'malick@jca.ca']);
    }

    public function test_nouveau_membre_doit_changer_son_mot_de_passe(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/users', [
            'name' => 'Test', 'email' => 'test@jca.ca', 'password' => 'motdepasse123', 'role' => 'assistant',
        ]);

        $membre = User::where('email', 'test@jca.ca')->first();
        $this->assertTrue($membre->doit_changer_mot_de_passe);
    }

    public function test_reinitialiser_le_mot_de_passe_force_le_changement(): void
    {
        $admin = User::factory()->admin()->create();
        $membre = User::factory()->create(['doit_changer_mot_de_passe' => false]);

        $this->actingAs($admin, 'sanctum')->putJson("/api/users/{$membre->id}", [
            'password' => 'nouveauMotDePasse123',
        ]);

        $this->assertTrue($membre->fresh()->doit_changer_mot_de_passe);
    }

    public function test_email_utilisateur_doit_etre_unique(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existe@jca.ca']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Autre', 'email' => 'existe@jca.ca', 'password' => 'motdepasse123', 'role' => 'assistant',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_mot_de_passe_trop_court_refuse(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Test', 'email' => 'court@jca.ca', 'password' => '123', 'role' => 'assistant',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
