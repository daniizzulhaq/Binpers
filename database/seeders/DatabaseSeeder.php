<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
        ]);

        // Data demo hanya untuk lingkungan pengembangan.
        if (app()->environment('local', 'testing')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
