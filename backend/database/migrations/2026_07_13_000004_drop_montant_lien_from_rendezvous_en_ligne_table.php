<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->dropColumn('montant_consultation');
        });
    }

    public function down(): void
    {
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->decimal('montant_consultation', 8, 2)->nullable();
        });
    }
};
