<?php

namespace Tests\Feature;

use App\Mail\ConfirmationRendezVousMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RendezVousPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_renvoie_des_creneaux_disponibles_generiques(): void
    {
        // Le client ne choisit plus d'avocat précis à cette étape — les
        // créneaux proposés sont désormais génériques (horaires du cabinet),
        // sans vérification de calendrier d'un avocat en particulier.
        $reponse = $this->getJson('/api/public/creneaux'
            . '?date_debut=' . now()->toDateString()
            . '&date_fin=' . now()->addDays(7)->toDateString())
            ->assertOk();

        $this->assertIsArray($reponse->json());
    }

    public function test_reserve_un_creneau_sans_avocat_et_envoie_un_email(): void
    {
        Mail::fake();

        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Jean Dupont',
            'email' => 'jean@test.com',
            'telephone' => '5145550000',
            'type_affaire' => 'action_humanitaire',
            'motif' => 'Litige avec mon employeur suite à un licenciement.',
            'date_heure' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
        ])->assertCreated();

        Mail::assertSent(ConfirmationRendezVousMail::class);
        $this->assertDatabaseHas('rendezvous_en_ligne', ['email' => 'jean@test.com', 'statut' => 'demande', 'avocat_id' => null, 'type_affaire' => 'action_humanitaire']);
    }

    public function test_motif_est_obligatoire(): void
    {
        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Jean Dupont',
            'email' => 'jean@test.com',
            'telephone' => '5145550000',
            'type_affaire' => 'action_humanitaire',
            'date_heure' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
        ])->assertStatus(422)->assertJsonValidationErrors(['motif']);
    }

    public function test_type_affaire_est_obligatoire(): void
    {
        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Jean Dupont',
            'email' => 'jean@test.com',
            'telephone' => '5145550000',
            'motif' => 'Une question juridique.',
            'date_heure' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
        ])->assertStatus(422)->assertJsonValidationErrors(['type_affaire']);
    }

    public function test_cree_automatiquement_une_fiche_client(): void
    {
        Mail::fake();

        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Marie Client',
            'email' => 'marie@test.com',
            'telephone' => '5145550001',
            'type_affaire' => 'immigration_mobilite',
            'sous_categories_affaire' => ['permis_etudes'],
            'motif' => 'Demande de renseignements sur un dossier d\'immigration.',
            'date_heure' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
        ])->assertCreated();

        $this->assertDatabaseHas('clients', ['email' => 'marie@test.com']);
    }

    public function test_refuse_immigration_sans_sous_categorie(): void
    {
        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Paul Client',
            'email' => 'paul@test.com',
            'telephone' => '5145550002',
            'type_affaire' => 'immigration_mobilite',
            'motif' => 'Question sur un permis.',
            'date_heure' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
        ])->assertStatus(422);
    }
}
