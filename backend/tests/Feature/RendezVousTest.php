<?php

namespace Tests\Feature;

use App\Mail\RendezVousAnnuleMail;
use App\Mail\RendezVousConfirmeMail;
use App\Models\RendezVousEnLigne;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RendezVousTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_exige_un_montant(): void
    {
        $admin = User::factory()->admin()->create();
        $rdv = RendezVousEnLigne::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/rendez-vous/{$rdv->id}/confirmer", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['montant_consultation']);
    }

    public function test_confirmation_envoie_un_email_avec_montant_et_lien(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $rdv = RendezVousEnLigne::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/rendez-vous/{$rdv->id}/confirmer", [
                'montant_consultation' => 200,
                'lien_rencontre' => 'https://meet.google.com/abc-defg-hij',
                'duree_minutes' => 90,
            ])
            ->assertOk();

        $this->assertEquals('confirme', $rdv->fresh()->statut);
        Mail::assertSent(RendezVousConfirmeMail::class, fn ($mail) => $mail->montantConsultation === 200.0 && $mail->dureeMinutes === 90);
    }

    public function test_montant_et_lien_ne_sont_jamais_enregistres_en_base(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $rdv = RendezVousEnLigne::factory()->create();

        $this->actingAs($admin, 'sanctum')->postJson("/api/rendez-vous/{$rdv->id}/confirmer", [
            'montant_consultation' => 150,
            'lien_rencontre' => 'https://teams.microsoft.com/xyz',
            'duree_minutes' => 60,
        ]);

        $this->assertDatabaseMissing('rendezvous_en_ligne', ['id' => $rdv->id, 'montant_consultation' => 150]);
    }

    public function test_annulation_envoie_un_email_seulement_si_deja_confirme(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        $demande = RendezVousEnLigne::factory()->create(['statut' => 'demande']);
        $this->actingAs($admin, 'sanctum')->postJson("/api/rendez-vous/{$demande->id}/annuler");
        Mail::assertNotSent(RendezVousAnnuleMail::class);

        $confirme = RendezVousEnLigne::factory()->confirme()->create();
        $this->actingAs($admin, 'sanctum')->postJson("/api/rendez-vous/{$confirme->id}/annuler");
        Mail::assertSent(RendezVousAnnuleMail::class);
    }

    public function test_les_demandes_en_attente_remontent_toujours_en_tete(): void
    {
        $admin = User::factory()->admin()->create();

        RendezVousEnLigne::factory()->confirme()->create(['date_heure' => now()->addDay()]);
        $demandeRecente = RendezVousEnLigne::factory()->create(['date_heure' => now()->addDays(10)]);

        $reponse = $this->actingAs($admin, 'sanctum')->getJson('/api/rendez-vous')->assertOk();

        $this->assertEquals($demandeRecente->id, $reponse->json('data.0.id'));
    }
}
