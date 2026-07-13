<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (utilisé pour les tests) ne gère pas nativement les ENUM comme
        // MySQL — Laravel les implémente déjà comme une simple colonne texte
        // avec une contrainte CHECK à la création, mais modifier cette
        // contrainte demanderait de reconstruire toute la table. La validation
        // applicative (Rule::in) suffit pour les tests, donc on ignore
        // simplement ce changement de contrainte sur SQLite.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'avocat', 'assistant', 'stagiaire') NOT NULL DEFAULT 'assistant'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Reconvertit d'abord les éventuels stagiaires en assistant, pour éviter
        // une valeur invalide après retrait de 'stagiaire' de l'ENUM.
        DB::statement("UPDATE users SET role = 'assistant' WHERE role = 'stagiaire'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'avocat', 'assistant') NOT NULL DEFAULT 'assistant'");
    }
};
