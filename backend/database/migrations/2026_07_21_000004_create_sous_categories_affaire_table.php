<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sous_categories_affaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_affaire_id')->constrained('types_affaire')->cascadeOnDelete();
            $table->string('slug');
            $table->string('libelle');
            $table->boolean('actif')->default(true);
            $table->integer('ordre')->default(0);
            $table->timestamps();
            $table->unique(['type_affaire_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sous_categories_affaire');
    }
};
