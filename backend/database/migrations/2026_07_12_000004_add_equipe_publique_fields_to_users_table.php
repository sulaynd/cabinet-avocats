<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('afficher_equipe_publique')->default(false)->after('phone');
            $table->string('titre_public')->nullable()->after('afficher_equipe_publique');
            $table->text('bio_publique')->nullable()->after('titre_public');
            $table->string('photo_chemin')->nullable()->after('bio_publique');
            $table->unsignedInteger('ordre_equipe')->default(0)->after('photo_chemin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['afficher_equipe_publique', 'titre_public', 'bio_publique', 'photo_chemin', 'ordre_equipe']);
        });
    }
};
