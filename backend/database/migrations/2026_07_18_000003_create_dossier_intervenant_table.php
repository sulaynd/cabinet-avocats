<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_intervenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('intervenant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['dossier_id', 'intervenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_intervenant');
    }
};
