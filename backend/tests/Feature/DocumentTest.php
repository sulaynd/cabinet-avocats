<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_televerse_un_document(): void
    {
        Storage::fake('local');
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/documents", [
            'fichier' => UploadedFile::fake()->create('contrat.pdf', 100),
            'type' => 'contrat',
        ])->assertCreated();

        $this->assertDatabaseHas('documents', ['nom_original' => 'contrat.pdf', 'dossier_id' => $dossier->id]);
    }

    public function test_refuse_le_televersement_sur_un_dossier_non_assigne(): void
    {
        Storage::fake('local');
        $avocat = User::factory()->avocat()->create();
        $autreAvocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $autreAvocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier->id}/documents", [
            'fichier' => UploadedFile::fake()->create('test.pdf', 100),
        ])->assertStatus(403);
    }

    public function test_telecharge_un_document(): void
    {
        Storage::fake('local');
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        Storage::disk('local')->put('dossiers/1/test.pdf', 'contenu factice');
        $document = Document::factory()->create(['dossier_id' => $dossier->id, 'chemin' => 'dossiers/1/test.pdf']);

        $this->actingAs($avocat, 'sanctum')
            ->get("/api/documents/{$document->id}/telecharger")
            ->assertOk();
    }

    public function test_demande_une_signature(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $document = Document::factory()->create(['dossier_id' => $dossier->id]);

        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/documents/{$document->id}/demander-signature", ['necessite_signature' => true])
            ->assertOk();

        $this->assertTrue($document->fresh()->necessite_signature);
    }

    public function test_supprime_un_document(): void
    {
        Storage::fake('local');
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $document = Document::factory()->create(['dossier_id' => $dossier->id]);

        $this->actingAs($avocat, 'sanctum')
            ->deleteJson("/api/documents/{$document->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }
}
