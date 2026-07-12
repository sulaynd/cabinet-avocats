<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabinet_settings', function (Blueprint $table) {
            $table->id();
            // Jeton du calendrier collectif de l'équipe (une seule ligne, singleton).
            $table->string('ical_token_equipe', 64)->unique();
            $table->timestamps();
        });

        // Ligne singleton créée immédiatement pour que la fonctionnalité soit
        // utilisable dès l'installation, sans seeder séparé.
        \Illuminate\Support\Facades\DB::table('cabinet_settings')->insert([
            'ical_token_equipe' => Str::random(40),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cabinet_settings');
    }
};
