<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            // Type d'affaire ciblé (civil, penal, ...) ; null = questionnaire par
            // défaut utilisé pour tous les types d'affaire sans questionnaire dédié.
            $table->string('type_affaire')->nullable();
            // Structure des champs : [{ "cle": "situation_familiale", "label": "...",
            // "type": "texte|choix|case|zone_texte", "options": [...], "requis": true }, ...]
            $table->json('champs');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaires');
    }
};
