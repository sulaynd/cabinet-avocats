<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            // Choix multiples (JSON) parmi les sous-catégories spécifiques à
            // "Immigration & mobilité internationale" — permis de travail,
            // permis d'études, visa visiteur, parrainage, entrée express,
            // demande humanitaire. Obligatoire quand ce type d'affaire est
            // choisi (voir validation du contrôleur), sans quoi ignoré.
            $table->json('sous_categories_immigration')->nullable()->after('type_affaire');
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropColumn('sous_categories_immigration');
        });
    }
};
