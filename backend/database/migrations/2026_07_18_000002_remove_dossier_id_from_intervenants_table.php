<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intervenants', function (Blueprint $table) {
            $table->dropForeign(['dossier_id']);
            $table->dropColumn('dossier_id');
        });
    }

    public function down(): void
    {
        Schema::table('intervenants', function (Blueprint $table) {
            $table->foreignId('dossier_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }
};
