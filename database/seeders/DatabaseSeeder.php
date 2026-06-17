<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            PlanSeeder::class,
            UserSeeder::class,
            ConteudoSeeder::class,
            MaterialSeeder::class,
            ClienteLicenciadoSeeder::class,
        ]);
    }
}
