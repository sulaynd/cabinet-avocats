<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\ModeleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModeleDocumentTest extends TestCase
{
    use RefreshDatabase;

    private const MIME_DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public function test_admin_peut_televerser_un_modele(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/modeles-documents', [
            'nom' => 'Mise en demeure',
            'fichier' => UploadedFile::fake()->create('modele.docx', 100, self::MIME_DOCX),
        ])->assertCreated();

        $this->assertDatabaseHas('modeles_documents', ['nom' => 'Mise en demeure']);
    }

    public function test_non_admin_ne_peut_pas_televerser_un_modele(): void
    {
        Storage::fake('local');
        $avocat = User::factory()->avocat()->create();

        $this->actingAs($avocat, 'sanctum')->postJson('/api/modeles-documents', [
            'nom' => 'Test',
            'fichier' => UploadedFile::fake()->create('modele.docx', 100, self::MIME_DOCX),
        ])->assertStatus(403);
    }

    public function test_refuse_un_fichier_qui_nest_pas_docx(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/modeles-documents', [
            'nom' => 'Test',
            'fichier' => UploadedFile::fake()->create('modele.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_admin_peut_supprimer_un_modele(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $modele = ModeleDocument::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/modeles-documents/{$modele->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('modeles_documents', ['id' => $modele->id]);
    }

    public function test_liste_les_modeles_pertinents_pour_un_dossier(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id, 'type_affaire' => 'immigration_mobilite']);
        ModeleDocument::factory()->create(['nom' => 'Spécifique immigration', 'type_affaire' => 'immigration_mobilite']);
        ModeleDocument::factory()->create(['nom' => 'Générique', 'type_affaire' => null]);
        ModeleDocument::factory()->create(['nom' => 'Autre domaine', 'type_affaire' => 'action_humanitaire']);

        $reponse = $this->actingAs($avocat, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/modeles-documents")
            ->assertOk();

        $noms = collect($reponse->json())->pluck('nom');
        $this->assertTrue($noms->contains('Spécifique immigration'));
        $this->assertTrue($noms->contains('Générique'));
        $this->assertFalse($noms->contains('Autre domaine'));
    }
}
