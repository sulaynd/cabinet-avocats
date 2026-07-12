<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Signature électronique "simple" (piste d'audit horodatée), pas une signature
            // qualifiée eIDAS. Voir CONFIGURATION.md pour l'intégration d'un prestataire
            // certifié (Yousign, DocuSign, Universign...) si une valeur probante forte est requise.
            $table->boolean('necessite_signature')->default(false)->after('uploaded_by');
            $table->timestamp('signe_le')->nullable()->after('necessite_signature');
            $table->string('signature_nom')->nullable()->after('signe_le');
            $table->string('signature_ip')->nullable()->after('signature_nom');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['necessite_signature', 'signe_le', 'signature_nom', 'signature_ip']);
        });
    }
};
