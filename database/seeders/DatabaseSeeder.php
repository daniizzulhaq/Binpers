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
            AssessmentRuleSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call([
                DemoDataSeeder::class,
                StructureDemoSeeder::class,
            ]);
        }
    }
}
