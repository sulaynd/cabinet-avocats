<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remplace le taux de TVA unique par les deux taxes distinctes en usage au
 * Québec : TPS (fédérale, 5 %) et TVQ (provinciale, 9,975 %), calculées
 * indépendamment sur le même montant HT (règle en vigueur depuis 2013 —
 * plus de "taxe sur taxe"). L'ancienne colonne taux_tva est conservée
 * (non utilisée par le nouveau code) pour ne perdre aucune donnée existante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->decimal('taux_tps', 5, 3)->default(5.000)->after('taux_tva');
            $table->decimal('taux_tvq', 5, 3)->default(9.975)->after('taux_tps');
            $table->decimal('montant_tps', 10, 2)->default(0)->after('montant_ht');
            $table->decimal('montant_tvq', 10, 2)->default(0)->after('montant_tps');
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn(['taux_tps', 'taux_tvq', 'montant_tps', 'montant_tvq']);
        });
    }
};
