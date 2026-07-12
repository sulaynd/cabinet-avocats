<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Point d'entrée de `php artisan db:seed` (et de `migrate --seed`).
     * Se limite volontairement au strict nécessaire pour démarrer : le compte
     * admin initial. Les avocats/assistants/clients/dossiers se créent ensuite
     * depuis l'application elle-même, une fois connecté.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
