<?php

namespace Tests\Feature;

use App\Models\CollaborateurExterne;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollaborateurExterneTest extends TestCase
{
    use RefreshDatabase;

    // --- Gestion admin/cabinet ---

    public function test_cree_un_collaborateur_et_le_lie_a_un_dossier(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/collaborateurs-externes", [
            'nom' => 'Me Gagnon',
            'email' => 'gagnon@cabinet-externe.com',
        ])->assertCreated();

        $this->assertDatabaseHas('collaborateurs_externes', ['nom' => 'Me Gagnon']);
        $this->assertEquals(1, $dossier->collaborateursExternes()->count());
    }

    public function test_meme_collaborateur_peut_etre_lie_a_plusieurs_dossiers(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier1 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $dossier2 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $collaborateur = CollaborateurExterne::factory()->create();

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier1->id}/collaborateurs-externes/{$collaborateur->id}/lier")->assertCreated();
        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier2->id}/collaborateurs-externes/{$collaborateur->id}/lier")->assertCreated();

        $this->assertEquals(1, $dossier1->collaborateursExternes()->count());
        $this->assertEquals(1, $dossier2->collaborateursExternes()->count());
    }

    public function test_activer_envoie_un_email_avec_mot_de_passe_temporaire(): void
    {
        Mail::fake();
        $avocat = User::factory()->avocat()->create();
        $collaborateur = CollaborateurExterne::factory()->create();

        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/collaborateurs-externes/{$collaborateur->id}/activer")
            ->assertOk();

        Mail::assertSent(\App\Mail\CollaborateurActivationMail::class);
        $this->assertNotNull($collaborateur->fresh()->portail_active_le);
    }

    // --- Authentification portail ---

    public function test_connexion_refusee_avant_activation(): void
    {
        $collaborateur = CollaborateurExterne::factory()->create();

        $this->postJson('/api/collaborateur/connexion', [
            'email' => $collaborateur->email,
            'password' => 'nimportequoi',
        ])->assertStatus(401);
    }

    public function test_connexion_reussie_apres_activation(): void
    {
        $collaborateur = CollaborateurExterne::factory()->avecAcces('MotDePasse123')->create();

        $this->postJson('/api/collaborateur/connexion', [
            'email' => $collaborateur->email,
            'password' => 'MotDePasse123',
        ])->assertOk()->assertJsonStructure(['collaborateur', 'token']);
    }

    // --- Accès aux documents partagés ---

    public function test_voit_uniquement_les_dossiers_auxquels_il_est_lie(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossierLie = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        Dossier::factory()->create(['avocat_id' => $avocat->id]); // non lié
        $collaborateur = CollaborateurExterne::factory()->avecAcces()->create();
        $dossierLie->collaborateursExternes()->attach($collaborateur->id);

        $reponse = $this->actingAs($collaborateur, 'sanctum')
            ->getJson('/api/collaborateur/mes-dossiers')
            ->assertOk();

        $this->assertCount(1, $reponse->json());
    }

    public function test_ne_voit_que_les_documents_explicitement_partages(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $collaborateur = CollaborateurExterne::factory()->avecAcces()->create();
        $dossier->collaborateursExternes()->attach($collaborateur->id);

        Document::factory()->create(['dossier_id' => $dossier->id, 'partage_externe' => true, 'nom_original' => 'Partagé.pdf']);
        Document::factory()->create(['dossier_id' => $dossier->id, 'partage_externe' => false, 'nom_original' => 'Confidentiel.pdf']);

        $reponse = $this->actingAs($collaborateur, 'sanctum')
            ->getJson("/api/collaborateur/dossiers/{$dossier->id}/documents")
            ->assertOk();

        $this->assertCount(1, $reponse->json());
        $this->assertEquals('Partagé.pdf', $reponse->json('0.nom_original'));
    }

    public function test_refuse_lacces_a_un_dossier_non_lie(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $collaborateur = CollaborateurExterne::factory()->avecAcces()->create();
        // Pas de liaison.

        $this->actingAs($collaborateur, 'sanctum')
            ->getJson("/api/collaborateur/dossiers/{$dossier->id}/documents")
            ->assertStatus(403);
    }

    public function test_peut_televerser_un_document_qui_devient_automatiquement_partage(): void
    {
        Storage::fake('local');
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $collaborateur = CollaborateurExterne::factory()->avecAcces()->create();
        $dossier->collaborateursExternes()->attach($collaborateur->id);

        $this->actingAs($collaborateur, 'sanctum')->postJson("/api/collaborateur/dossiers/{$dossier->id}/documents", [
            'fichier' => UploadedFile::fake()->create('piece.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $this->assertDatabaseHas('documents', ['dossier_id' => $dossier->id, 'partage_externe' => true, 'collaborateur_externe_id' => $collaborateur->id]);
    }

    public function test_un_token_interne_ne_fonctionne_pas_sur_le_portail_collaborateur(): void
    {
        $avocat = User::factory()->avocat()->create();

        $this->actingAs($avocat, 'sanctum')
            ->getJson('/api/collaborateur/mes-dossiers')
            ->assertStatus(403);
    }

    // --- Bascule de partage sur un document existant ---

    public function test_admin_peut_marquer_un_document_comme_partage(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $document = Document::factory()->create(['dossier_id' => $dossier->id, 'partage_externe' => false]);

        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/documents/{$document->id}/partager-externe", ['partage_externe' => true])
            ->assertOk();

        $this->assertTrue((bool) $document->fresh()->partage_externe);
    }
}
