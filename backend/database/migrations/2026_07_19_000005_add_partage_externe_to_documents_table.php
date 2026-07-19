<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('partage_externe')->default(false)->after('necessite_signature');
            $table->foreignId('collaborateur_externe_id')->nullable()->after('uploaded_by')->constrained('collaborateurs_externes')->nullOnDelete();
        });

        // Un document est désormais téléversé soit par un membre du cabinet
        // (uploaded_by), soit par un collaborateur externe
        // (collaborateur_externe_id) — les deux ne peuvent plus être requis
        // simultanément, donc uploaded_by doit devenir facultatif.
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['collaborateur_externe_id']);
            $table->dropColumn(['partage_externe', 'collaborateur_externe_id']);
            $table->foreignId('uploaded_by')->nullable(false)->change();
        });
    }
};
