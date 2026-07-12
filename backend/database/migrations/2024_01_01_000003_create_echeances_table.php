<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echeances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->string('titre');
            $table->enum('type', ['audience', 'delai_procedural', 'rdv_client', 'autre'])->default('autre');
            $table->dateTime('date_heure');
            $table->string('lieu')->nullable();
            $table->enum('statut', ['a_venir', 'realisee', 'annulee'])->default('a_venir');
            $table->unsignedInteger('rappel_avant')->nullable()->comment('minutes avant la date_heure');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echeances');
    }
};
