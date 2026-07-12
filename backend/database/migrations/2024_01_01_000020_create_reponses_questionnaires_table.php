<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reponses_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            // Jeton secret du lien public envoyé au client (pas d'authentification
            // nécessaire pour remplir son propre questionnaire de pré-consultation).
            $table->string('token', 64)->unique();
            $table->json('reponses')->nullable();
            $table->timestamp('envoye_le')->nullable();
            $table->timestamp('rempli_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_questionnaires');
    }
};
