<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed safe reference data only. User accounts are created with `php artisan zi:install`
     * so no account with a fixed password ever exists.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            VerificationStageSeeder::class,
            ReferenceDataSeeder::class,
        ]);
    }
}
