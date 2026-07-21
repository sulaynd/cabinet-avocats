<?php

namespace Database\Seeders;

use App\Models\SousCategorieAffaire;
use App\Models\TypeAffaire;
use Illuminate\Database\Seeder;

class TypesAffaireSeeder extends Seeder
{
    public function run(): void
    {
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
            TypeAffaire::firstOrCreate(['slug' => $type['slug']], $type);
        }

        $immigration = TypeAffaire::where('slug', 'immigration_mobilite')->first();

        $sousCategories = [
            ['slug' => 'permis_travail', 'libelle' => 'Permis de travail', 'ordre' => 1],
            ['slug' => 'permis_etudes', 'libelle' => "Permis d'études", 'ordre' => 2],
            ['slug' => 'visa_visiteur', 'libelle' => 'Visa visiteur', 'ordre' => 3],
            ['slug' => 'parrainage', 'libelle' => 'Parrainage', 'ordre' => 4],
            ['slug' => 'entree_express', 'libelle' => 'Entrée express', 'ordre' => 5],
            ['slug' => 'demande_humanitaire', 'libelle' => 'Demande humanitaire', 'ordre' => 6],
        ];

        foreach ($sousCategories as $sc) {
            SousCategorieAffaire::firstOrCreate(
                ['type_affaire_id' => $immigration->id, 'slug' => $sc['slug']],
                array_merge($sc, ['type_affaire_id' => $immigration->id])
            );
        }
    }
}
