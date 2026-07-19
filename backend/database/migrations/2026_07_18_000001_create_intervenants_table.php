<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->enum('fonction', [
                'avocat_adverse', 'expert', 'greffier', 'huissier', 'mediateur_arbitre', 'notaire', 'autre',
            ])->default('autre');
            $table->string('organisation')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervenants');
    }
};
