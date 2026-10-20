<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['hrd', 'Admin HRD', RoleName::Hrd],
            ['kapolres', 'Kapolres Demo', RoleName::Kapolres],
            ['wakapolres', 'Wakapolres Demo', RoleName::Wakapolres],
            ['kabag', 'Kabag Demo', RoleName::Kabag],
            ['kasat', 'Kasat Demo', RoleName::Kasat],
            ['kasi', 'Kasi Demo', RoleName::Kasi],
            ['kanit', 'Kanit Demo', RoleName::Kanit],
            ['anggota', 'Anggota Demo', RoleName::Anggota],
        ];

        foreach ($users as [$username, $name, $role]) {
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => $username.'@polres.test',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$role->value]);
        }
    }
}
