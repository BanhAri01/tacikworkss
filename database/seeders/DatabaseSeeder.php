<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            AdminUserSeeder::class,
            CatalogSeeder::class,
        ]);

        // Demo data never runs in production.
        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
