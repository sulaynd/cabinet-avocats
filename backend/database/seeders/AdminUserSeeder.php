<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crée le premier compte admin du cabinet, pour pouvoir se connecter et
 * commencer à créer les avocats/assistants depuis l'interface elle-même.
 *
 * Utilise updateOrCreate() : relancer ce seeder est sans danger, il met juste
 * à jour le mot de passe/rôle de ce même compte plutôt que d'en créer un doublon.
 *
 *   php artisan db:seed --class=AdminUserSeeder
 *
 * Identifiants configurables via .env (sinon valeurs par défaut ci-dessous) :
 *   ADMIN_EMAIL=admin@jca.ca
 *   ADMIN_PASSWORD=un-mot-de-passe-fort
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@jca.ca');
        $password = env('ADMIN_PASSWORD', 'changez-moi-immediatement');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrateur du cabinet'),
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );

        $this->command->info("Compte admin prêt : {$email}");

        if ($password === 'changez-moi-immediatement') {
            $this->command->warn(
                "⚠️  Mot de passe par défaut utilisé — changez-le dès la première connexion, ".
                "ou définissez ADMIN_PASSWORD dans .env avant de relancer ce seeder."
            );
        }
    }
}
