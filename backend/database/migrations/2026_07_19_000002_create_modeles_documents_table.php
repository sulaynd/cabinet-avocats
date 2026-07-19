<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modeles_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->enum('type_affaire', [
                'immigration_mobilite', 'recrutement_international', 'cooperation_internationale',
                'developpement_international', 'action_humanitaire', 'conseils_strategiques', 'autre',
            ])->nullable();
            $table->string('fichier_chemin');
            $table->string('nom_original');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles_documents');
    }
};
