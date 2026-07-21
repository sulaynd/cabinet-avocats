<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Insère les données de référence directement dans la migration (plutôt
     * qu'un seeder séparé à lancer manuellement) — garantit qu'elles existent
     * systématiquement après php artisan migrate, en production comme dans
     * chaque suite de tests (RefreshDatabase rejoue toutes les migrations).
     */
    public function up(): void
    {
        $maintenant = now();

        $types = [
            ['slug' => 'immigration_mobilite', 'libelle' => 'Immigration & mobilité internationale', 'ordre' => 1],
            ['slug' => 'recrutement_international', 'libelle' => 'Recrutement international', 'ordre' => 2],
            ['slug' => 'cooperation_internationale', 'libelle' => 'Coopération internationale', 'ordre' => 3],
            ['slug' => 'developpement_international', 'libelle' => 'Développement international', 'ordre' => 4],
            ['slug' => 'action_humanitaire', 'libelle' => 'Action humanitaire', 'ordre' => 5],
            ['slug' => 'conseils_strategiques', 'libelle' => 'Services-conseils stratégiques', 'ordre' => 6],
            ['slug' => 'autre', 'libelle' => 'Autre', 'ordre' => 7],
        ];

        foreach ($types as $type) {
            DB::table('types_affaire')->insertOrIgnore(array_merge($type, [
                'actif' => true, 'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]));
        }

        $idImmigration = DB::table('types_affaire')->where('slug', 'immigration_mobilite')->value('id');

        $sousCategories = [
            ['slug' => 'permis_travail', 'libelle' => 'Permis de travail', 'ordre' => 1],
            ['slug' => 'permis_etudes', 'libelle' => "Permis d'études", 'ordre' => 2],
            ['slug' => 'visa_visiteur', 'libelle' => 'Visa visiteur', 'ordre' => 3],
            ['slug' => 'parrainage', 'libelle' => 'Parrainage', 'ordre' => 4],
            ['slug' => 'entree_express', 'libelle' => 'Entrée express', 'ordre' => 5],
            ['slug' => 'demande_humanitaire', 'libelle' => 'Demande humanitaire', 'ordre' => 6],
        ];

        foreach ($sousCategories as $sc) {
            DB::table('sous_categories_affaire')->insertOrIgnore(array_merge($sc, [
                'type_affaire_id' => $idImmigration, 'actif' => true, 'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('sous_categories_affaire')->whereIn('slug', [
            'permis_travail', 'permis_etudes', 'visa_visiteur', 'parrainage', 'entree_express', 'demande_humanitaire',
        ])->delete();

        DB::table('types_affaire')->whereIn('slug', [
            'immigration_mobilite', 'recrutement_international', 'cooperation_internationale',
            'developpement_international', 'action_humanitaire', 'conseils_strategiques', 'autre',
        ])->delete();
    }
};
