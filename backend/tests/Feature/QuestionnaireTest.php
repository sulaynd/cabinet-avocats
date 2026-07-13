<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\Questionnaire;
use App\Models\ReponseQuestionnaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_peut_creer_un_questionnaire(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/questionnaires', [
            'nom' => 'Questionnaire immigration',
            'champs' => [['cle' => 'nom', 'label' => 'Nom', 'type' => 'texte', 'requis' => true]],
            'actif' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('questionnaires', ['nom' => 'Questionnaire immigration']);
    }

    public function test_non_admin_ne_peut_pas_gerer_les_questionnaires(): void
    {
        $avocat = User::factory()->avocat()->create();

        $this->actingAs($avocat, 'sanctum')
            ->getJson('/api/questionnaires')
            ->assertStatus(403);
    }

    public function test_page_publique_affiche_le_questionnaire_via_le_jeton(): void
    {
        $dossier = Dossier::factory()->create();
        $questionnaire = Questionnaire::factory()->create();
        $reponse = ReponseQuestionnaire::factory()->create([
            'dossier_id' => $dossier->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        $this->getJson("/api/questionnaire/{$reponse->token}")
            ->assertOk()
            ->assertJsonPath('deja_rempli', false);
    }

    public function test_jeton_invalide_renvoie_404(): void
    {
        $this->getJson('/api/questionnaire/jeton-inexistant')->assertNotFound();
    }

    public function test_soumission_publique_enregistre_les_reponses(): void
    {
        $reponse = ReponseQuestionnaire::factory()->create();

        $this->postJson("/api/questionnaire/{$reponse->token}", [
            'reponses' => ['nom' => 'Jean Test'],
        ])->assertOk();

        $this->assertNotNull($reponse->fresh()->rempli_le);
    }

    public function test_refuse_une_double_soumission(): void
    {
        $reponse = ReponseQuestionnaire::factory()->rempli()->create();

        $this->postJson("/api/questionnaire/{$reponse->token}", [
            'reponses' => ['nom' => 'Nouvelle tentative'],
        ])->assertStatus(422);
    }
}
