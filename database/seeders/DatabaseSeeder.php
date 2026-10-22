<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SettingsSeeder::class,
            DemoStaffSeeder::class,
            DemoCatalogSeeder::class,
            DemoClinicalDataSeeder::class,
            DemoHrSeeder::class,
        ]);
    }
}
