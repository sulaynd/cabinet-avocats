<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Taux horaire par défaut de l'intervenant (avocat/assistant), utilisé quand
            // le dossier ne définit pas son propre taux_horaire.
            $table->decimal('taux_horaire_defaut', 8, 2)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('taux_horaire_defaut');
        });
    }
};
