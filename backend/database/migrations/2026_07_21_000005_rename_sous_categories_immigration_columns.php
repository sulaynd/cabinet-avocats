<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Les sous-catégories ne sont plus réservées à "Immigration & mobilité" —
        // n'importe quel type d'affaire peut désormais en avoir.
        Schema::table('dossiers', function (Blueprint $table) {
            $table->renameColumn('sous_categories_immigration', 'sous_categories_affaire');
        });
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->renameColumn('sous_categories_immigration', 'sous_categories_affaire');
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->renameColumn('sous_categories_affaire', 'sous_categories_immigration');
        });
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->renameColumn('sous_categories_affaire', 'sous_categories_immigration');
        });
    }
};
