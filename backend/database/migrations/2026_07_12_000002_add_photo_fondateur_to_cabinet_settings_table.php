<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cabinet_settings', function (Blueprint $table) {
            $table->string('photo_fondateur_chemin')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('cabinet_settings', function (Blueprint $table) {
            $table->dropColumn('photo_fondateur_chemin');
        });
    }
};
