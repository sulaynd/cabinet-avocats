<?php

namespace Tests\Feature;

use App\Models\Dossier;
use App\Models\TempsPasse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempsPasseTest extends TestCase
{
    use RefreshDatabase;

    public function test_demarre_un_chronometre(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier->id}/temps/demarrer", ['description' => 'Rédaction'])
            ->assertCreated();

        $this->assertDatabaseHas('temps_passes', ['dossier_id' => $dossier->id, 'termine_a' => null]);
    }

    public function test_refuse_un_second_chronometre_simultane(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier1 = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $dossier2 = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')->postJson("/api/dossiers/{$dossier1->id}/temps/demarrer");
        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier2->id}/temps/demarrer")
            ->assertStatus(422);
    }

    /** Non-régression du bug Carbon 3.x où diffInSeconds() sans absolute:true
     * renvoyait 0 (voire une valeur signée) au lieu de la vraie durée écoulée. */
    public function test_arreter_calcule_correctement_la_duree(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $temps = TempsPasse::factory()->enCours()->create([
            'dossier_id' => $dossier->id,
            'user_id' => $avocat->id,
            'demarre_a' => now()->subMinutes(30),
        ]);

        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/temps/{$temps->id}/arreter")
            ->assertOk();

        $duree = $temps->fresh()->duree_secondes;
        $this->assertGreaterThan(1700, $duree); // ~30 minutes, jamais 0
        $this->assertLessThan(1900, $duree);
    }

    public function test_ajoute_une_entree_manuelle(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier->id}/temps", ['duree_minutes' => 45])
            ->assertCreated();

        $this->assertDatabaseHas('temps_passes', ['dossier_id' => $dossier->id, 'duree_secondes' => 2700]);
    }

    public function test_refuse_le_temps_sur_un_dossier_clos(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->clos()->create(['avocat_id' => $avocat->id]);

        $this->actingAs($avocat, 'sanctum')
            ->postJson("/api/dossiers/{$dossier->id}/temps", ['duree_minutes' => 30])
            ->assertStatus(422);
    }

    public function test_ne_peut_pas_modifier_un_temps_deja_facture(): void
    {
        $avocat = User::factory()->avocat()->create();
        $dossier = Dossier::factory()->create(['avocat_id' => $avocat->id]);
        $facture = \App\Models\Facture::factory()->create(['dossier_id' => $dossier->id, 'client_id' => $dossier->client_id]);
        $temps = TempsPasse::factory()->create(['dossier_id' => $dossier->id, 'user_id' => $avocat->id, 'facture_id' => $facture->id]);

        $this->actingAs($avocat, 'sanctum')
            ->putJson("/api/temps/{$temps->id}", ['duree_minutes' => 60])
            ->assertStatus(422);
    }
}
