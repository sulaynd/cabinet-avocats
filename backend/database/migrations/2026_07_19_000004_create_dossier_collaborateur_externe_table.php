<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_collaborateur_externe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collaborateur_externe_id')->constrained('collaborateurs_externes')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['dossier_id', 'collaborateur_externe_id'], 'dossier_collab_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_collaborateur_externe');
    }
};
