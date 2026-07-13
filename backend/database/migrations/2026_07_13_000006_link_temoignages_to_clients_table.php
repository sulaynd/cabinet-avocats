<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temoignages', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->cascadeOnDelete();
            $table->dropColumn(['nom', 'titre_fonction']);
        });
    }

    public function down(): void
    {
        Schema::table('temoignages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->string('nom')->after('id');
            $table->string('titre_fonction')->nullable();
        });
    }
};
