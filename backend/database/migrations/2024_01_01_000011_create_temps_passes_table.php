<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temps_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('description')->nullable();
            // Chronomètre : demarre_a rempli et termine_a null = timer en cours.
            $table->dateTime('demarre_a')->nullable();
            $table->dateTime('termine_a')->nullable();
            // Durée en secondes : calculée automatiquement à l'arrêt du chrono,
            // ou saisie manuellement pour une entrée de temps ajoutée à la main.
            $table->unsignedInteger('duree_secondes')->default(0);
            $table->boolean('facturable')->default(true);
            // Taux horaire réellement appliqué à la facturation (snapshot au moment
            // de la génération de la facture, pour ne pas dépendre d'un taux qui change ensuite).
            $table->decimal('taux_horaire_applique', 8, 2)->nullable();
            // Renseigné une fois inclus dans une facture, pour ne jamais facturer deux fois le même temps.
            $table->foreignId('facture_id')->nullable()->constrained('factures')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temps_passes');
    }
};
