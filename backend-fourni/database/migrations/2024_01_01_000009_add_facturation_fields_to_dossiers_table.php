<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            // Mode de facturation du dossier : au temps passé (horaire) ou au forfait.
            $table->enum('mode_facturation', ['horaire', 'forfait'])->default('horaire')->after('statut');
            // Taux horaire propre au dossier ; si null, on retombe sur le taux par défaut de l'intervenant.
            $table->decimal('taux_horaire', 8, 2)->nullable()->after('mode_facturation');
            // Montant forfaitaire, utilisé uniquement si mode_facturation = 'forfait'.
            $table->decimal('montant_forfait', 10, 2)->nullable()->after('taux_horaire');
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropColumn(['mode_facturation', 'taux_horaire', 'montant_forfait']);
        });
    }
};
