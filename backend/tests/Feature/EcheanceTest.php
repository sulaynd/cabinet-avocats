<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\Echeance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcheanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cree_une_echeance(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/echeances', [
            'dossier_id' => $dossier->id,
            'titre' => 'Audience préliminaire',
            'type' => 'audience',
            'date_heure' => now()->addDays(10)->toDateTimeString(),
            'statut' => 'a_venir',
        ])->assertCreated();

        $this->assertDatabaseHas('echeances', ['titre' => 'Audience préliminaire']);
    }

    public function test_liste_les_echeances_dun_dossier(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create();
        Echeance::factory()->count(2)->create(['dossier_id' => $dossier->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/dossiers/{$dossier->id}/echeances")
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_type_invalide_refuse(): void
    {
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/echeances', [
            'dossier_id' => $dossier->id,
            'titre' => 'Test',
            'type' => 'type_invalide',
            'date_heure' => now()->addDay()->toDateTimeString(),
            'statut' => 'a_venir',
        ])->assertStatus(422);
    }

    public function test_detecte_un_conflit_pour_le_meme_avocat(): void
    {
        $admin = User::factory()->admin()->create();
        $avocat = User::factory()->avocat()->create();
        $dossier1 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $dossier2 = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        \App\Models\Echeance::factory()->create([
            'dossier_id' => $dossier1->id,
            'type' => 'audience',
            'statut' => 'a_venir',
            'date_heure' => '2026-08-10 10:00:00',
        ]);

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/echeances/verifier-conflits', [
            'dossier_id' => $dossier2->id,
            'date_heure' => '2026-08-10 10:30:00',
        ])->assertOk();

        $this->assertCount(1, $reponse->json());
    }

    public function test_aucun_conflit_si_avocats_differents(): void
    {
        $admin = User::factory()->admin()->create();
        $avocat1 = User::factory()->avocat()->create();
        $avocat2 = User::factory()->avocat()->create();
        $dossier1 = Dossier::factory()->create(['avocat_id' => $avocat1->id]);
        $dossier2 = Dossier::factory()->create(['avocat_id' => $avocat2->id]);

        \App\Models\Echeance::factory()->create([
            'dossier_id' => $dossier1->id,
            'type' => 'audience',
            'statut' => 'a_venir',
            'date_heure' => '2026-08-10 10:00:00',
        ]);

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/echeances/verifier-conflits', [
            'dossier_id' => $dossier2->id,
            'date_heure' => '2026-08-10 10:30:00',
        ])->assertOk();

        $this->assertCount(0, $reponse->json());
    }

    public function test_aucun_conflit_hors_de_la_fenetre_dune_heure(): void
    {
        $admin = User::factory()->admin()->create();
        $avocat = User::factory()->avocat()->create();
        $dossier1 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $dossier2 = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        \App\Models\Echeance::factory()->create([
            'dossier_id' => $dossier1->id,
            'type' => 'audience',
            'statut' => 'a_venir',
            'date_heure' => '2026-08-10 10:00:00',
        ]);

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/echeances/verifier-conflits', [
            'dossier_id' => $dossier2->id,
            'date_heure' => '2026-08-10 13:00:00',
        ])->assertOk();

        $this->assertCount(0, $reponse->json());
    }

    public function test_delai_procedural_najoute_jamais_de_conflit(): void
    {
        $admin = User::factory()->admin()->create();
        $avocat = User::factory()->avocat()->create();
        $dossier1 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $dossier2 = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        \App\Models\Echeance::factory()->create([
            'dossier_id' => $dossier1->id,
            'type' => 'delai_procedural',
            'statut' => 'a_venir',
            'date_heure' => '2026-08-10 10:00:00',
        ]);

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/echeances/verifier-conflits', [
            'dossier_id' => $dossier2->id,
            'date_heure' => '2026-08-10 10:00:00',
        ])->assertOk();

        $this->assertCount(0, $reponse->json());
    }

    public function test_exclut_lecheance_en_cours_de_modification(): void
    {
        $admin = User::factory()->admin()->create();
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $echeance = \App\Models\Echeance::factory()->create([
            'dossier_id' => $dossier->id,
            'type' => 'audience',
            'statut' => 'a_venir',
            'date_heure' => '2026-08-10 10:00:00',
        ]);

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/echeances/verifier-conflits', [
            'dossier_id' => $dossier->id,
            'date_heure' => '2026-08-10 10:00:00',
            'exclure_id' => $echeance->id,
        ])->assertOk();

        $this->assertCount(0, $reponse->json());
    }

    public function test_la_date_heure_renvoyee_correspond_exactement_a_celle_saisie(): void
    {
        // Reproduit le bug réel : Carbon convertit par défaut en UTC lors de
        // la sérialisation JSON (suffixe "Z"), décalant l'heure affichée par
        // rapport à celle réellement saisie par l'utilisateur.
        $admin = User::factory()->admin()->create();
        $dossier = Dossier::factory()->create();

        $reponse = $this->actingAs($admin, 'sanctum')->postJson('/api/echeances', [
            'dossier_id' => $dossier->id,
            'titre' => 'Test heure',
            'type' => 'audience',
            'date_heure' => '2026-07-21T10:00',
            'statut' => 'a_venir',
        ])->assertCreated();

        $this->assertEquals('2026-07-21T10:00:00', $reponse->json('date_heure'));
    }
}
