<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('avocat_id')->constrained('users')->cascadeOnDelete();
            $table->string('titre');
            $table->enum('type_affaire', ['civil', 'penal', 'commercial', 'famille', 'travail', 'immobilier', 'autre'])->default('autre');
            $table->enum('statut', ['ouvert', 'en_cours', 'en_attente', 'clos', 'archive'])->default('ouvert');
            $table->date('date_ouverture')->nullable();
            $table->date('date_cloture')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};
