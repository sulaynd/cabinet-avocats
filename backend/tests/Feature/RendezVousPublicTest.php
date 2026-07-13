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

    public function test_liste_publique_ne_montre_que_les_avocats(): void
    {
        User::factory()->avocat()->create(['name' => 'Me Avocat']);
        User::factory()->assistant()->create(['name' => 'Assistant']);

        $reponse = $this->getJson('/api/public/avocats')->assertOk();

        $noms = collect($reponse->json())->pluck('name');
        $this->assertTrue($noms->contains('Me Avocat'));
        $this->assertFalse($noms->contains('Assistant'));
    }

    public function test_renvoie_des_creneaux_disponibles(): void
    {
        $avocat = User::factory()->avocat()->create();

        $reponse = $this->getJson('/api/public/creneaux?avocat_id=' . $avocat->id
            . '&date_debut=' . now()->toDateString()
            . '&date_fin=' . now()->addDays(7)->toDateString())
            ->assertOk();

        $this->assertIsArray($reponse->json());
    }

    public function test_reserve_un_creneau_et_envoie_un_email_de_confirmation(): void
    {
        Mail::fake();
        $avocat = User::factory()->avocat()->create();

        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Jean Dupont',
            'email' => 'jean@test.com',
            'telephone' => '5145550000',
            'avocat_id' => $avocat->id,
            'date_heure' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
        ])->assertCreated();

        Mail::assertSent(ConfirmationRendezVousMail::class);
        $this->assertDatabaseHas('rendezvous_en_ligne', ['email' => 'jean@test.com', 'statut' => 'demande']);
    }

    public function test_cree_automatiquement_une_fiche_client(): void
    {
        Mail::fake();
        $avocat = User::factory()->avocat()->create();

        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Marie Client',
            'email' => 'marie@test.com',
            'telephone' => '5145550001',
            'avocat_id' => $avocat->id,
            'date_heure' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
        ])->assertCreated();

        $this->assertDatabaseHas('clients', ['email' => 'marie@test.com']);
    }

    public function test_refuse_un_creneau_deja_reserve(): void
    {
        Mail::fake();
        $avocat = User::factory()->avocat()->create();
        $dateHeure = now()->addDays(3)->setTime(10, 0)->toDateTimeString();

        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Premier', 'email' => 'premier@test.com', 'telephone' => '5145550000',
            'avocat_id' => $avocat->id, 'date_heure' => $dateHeure,
        ]);

        $this->postJson('/api/public/rendez-vous', [
            'nom' => 'Second', 'email' => 'second@test.com', 'telephone' => '5145550001',
            'avocat_id' => $avocat->id, 'date_heure' => $dateHeure,
        ])->assertStatus(409);
    }
}
