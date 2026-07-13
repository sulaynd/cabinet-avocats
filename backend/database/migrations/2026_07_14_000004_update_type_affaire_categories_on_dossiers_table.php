<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sécurité avant tout changement d'ENUM : bascule temporairement toutes
        // les valeurs existantes vers 'autre', pour qu'aucune ligne ne se
        // retrouve jamais avec une valeur invalide pendant la transition.
        DB::statement("UPDATE dossiers SET type_affaire = 'autre'");

        // SQLite (utilisé pour les tests) ne gère pas ALTER ... MODIFY COLUMN
        // comme MySQL ; la validation applicative (Rule::in) suffit pour les
        // tests, donc on ignore ce changement de contrainte sur SQLite.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE dossiers MODIFY COLUMN type_affaire ENUM(
            'immigration_mobilite',
            'recrutement_international',
            'cooperation_internationale',
            'developpement_international',
            'action_humanitaire',
            'conseils_strategiques',
            'autre'
        ) NOT NULL DEFAULT 'autre'");
    }

    public function down(): void
    {
        DB::statement("UPDATE dossiers SET type_affaire = 'autre'");

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE dossiers MODIFY COLUMN type_affaire ENUM(
            'civil', 'penal', 'commercial', 'famille', 'travail', 'immobilier', 'autre'
        ) NOT NULL DEFAULT 'autre'");
    }
};
