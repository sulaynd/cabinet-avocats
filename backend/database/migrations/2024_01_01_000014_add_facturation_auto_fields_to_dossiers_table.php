<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            // Facturation périodique automatique (ex. mensuelle) du temps non facturé.
            $table->boolean('facturation_periodique')->default(false)->after('montant_forfait');
            $table->enum('frequence_facturation', ['hebdomadaire', 'mensuelle'])->nullable()->after('facturation_periodique');
            // Génère + envoie automatiquement une facture quand le dossier passe au statut "clos".
            $table->boolean('facturer_a_cloture')->default(false)->after('frequence_facturation');
            $table->timestamp('derniere_facturation_auto_le')->nullable()->after('facturer_a_cloture');
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropColumn(['facturation_periodique', 'frequence_facturation', 'facturer_a_cloture', 'derniere_facturation_auto_le']);
        });
    }
};
