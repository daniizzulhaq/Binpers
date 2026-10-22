<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Akun admin HRD. Akun personel dibuat oleh PersonelPolresSeeder. */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(['username' => 'hrd'], [
            'name' => 'Admin HRD',
            'password' => Hash::make(env('HRD_DEFAULT_PASSWORD', 'password')),
            'is_active' => true,
        ]);

        $user->syncRoles([RoleName::Hrd->value]);
    }
}
