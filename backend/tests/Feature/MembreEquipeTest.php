<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembreEquipeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ne_montre_que_les_membres_ayant_active_laffichage_public(): void
    {
        User::factory()->create(['name' => 'Visible', 'afficher_equipe_publique' => true, 'titre_public' => 'Avocat associé']);
        User::factory()->create(['name' => 'Caché', 'afficher_equipe_publique' => false]);

        $reponse = $this->getJson('/api/membres-equipe/public')->assertOk();

        $noms = collect($reponse->json())->pluck('nom');
        $this->assertTrue($noms->contains('Visible'));
        $this->assertFalse($noms->contains('Caché'));
    }

    public function test_inclut_le_role_pour_le_regroupement_par_bande(): void
    {
        User::factory()->create(['role' => 'avocat', 'afficher_equipe_publique' => true]);

        $reponse = $this->getJson('/api/membres-equipe/public')->assertOk();

        $this->assertEquals('avocat', $reponse->json('0.role'));
    }
}
