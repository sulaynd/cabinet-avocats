<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membres_equipe', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('titre')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_chemin')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres_equipe');
    }
};
