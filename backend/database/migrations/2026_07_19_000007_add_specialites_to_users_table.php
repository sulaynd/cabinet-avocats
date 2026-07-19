<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Liste des types d'affaire (JSON) qu'un avocat traite — sert à la
            // suggestion d'assignation automatique des nouveaux dossiers.
            // Vide = pas de spécialité déclarée (traité comme "généraliste",
            // éligible à tous les types dans la suggestion).
            $table->json('specialites')->nullable()->after('taux_horaire_defaut');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('specialites');
        });
    }
};
