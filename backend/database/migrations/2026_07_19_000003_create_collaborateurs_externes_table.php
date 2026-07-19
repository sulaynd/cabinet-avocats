<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaborateurs_externes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('email')->unique();
            $table->string('organisation')->nullable();
            $table->string('telephone')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('portail_active_le')->nullable();
            $table->boolean('doit_changer_mot_de_passe')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaborateurs_externes');
    }
};
