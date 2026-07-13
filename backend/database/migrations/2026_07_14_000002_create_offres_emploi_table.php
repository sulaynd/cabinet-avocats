<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offres_emploi', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description');
            $table->enum('type_contrat', ['cdi', 'cdd', 'stage', 'temps_partiel', 'contractuel', 'autre'])->default('cdi');
            $table->string('lieu')->nullable();
            $table->date('date_limite')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('actif')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offres_emploi');
    }
};
