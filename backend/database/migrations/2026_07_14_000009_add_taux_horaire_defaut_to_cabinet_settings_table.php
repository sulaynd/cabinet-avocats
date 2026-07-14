<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cabinet_settings', function (Blueprint $table) {
            $table->decimal('taux_horaire_defaut', 8, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cabinet_settings', function (Blueprint $table) {
            $table->dropColumn('taux_horaire_defaut');
        });
    }
};
