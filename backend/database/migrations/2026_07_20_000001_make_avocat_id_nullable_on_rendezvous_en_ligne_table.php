<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le client ne choisit plus l'avocat à la prise de rendez-vous — il
        // décrit son besoin (motif, désormais obligatoire), et le cabinet
        // assigne lui-même l'avocat à la confirmation, selon les disponibilités
        // réelles de chacun.
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->foreignId('avocat_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->foreignId('avocat_id')->nullable(false)->change();
        });
    }
};
