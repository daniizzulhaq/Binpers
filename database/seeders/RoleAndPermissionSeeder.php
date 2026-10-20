<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.manage',
            'personnel.manage',
            'organization.manage',
            'positions.manage',
            'authorizations.manage',
            'assessment-rules.manage',
            'periods.manage',
            'criteria.manage',
            'assessments.create',
            'assessments.approve',
            'assessments.finalize',
            'assessments.view-own',
            'rewards.manage',
            'reports.view',
            'analytics.view',
            'audit-logs.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $assessor = ['assessments.create', 'assessments.view-own'];

        $map = [
            RoleName::Hrd->value => [
                'users.manage', 'personnel.manage', 'organization.manage', 'positions.manage',
                'authorizations.manage', 'assessment-rules.manage', 'periods.manage', 'criteria.manage',
                'assessments.approve', 'assessments.finalize',
                'rewards.manage', 'reports.view', 'analytics.view', 'audit-logs.view',
            ],
            RoleName::Kapolres->value => ['assessments.create', 'assessments.approve', 'reports.view', 'analytics.view'],
            RoleName::Wakapolres->value => ['assessments.create', 'assessments.approve', 'reports.view', 'analytics.view'],
            RoleName::Kabag->value => $assessor,
            RoleName::Kasat->value => $assessor,
            RoleName::KasiPengawas->value => $assessor,
            RoleName::Kasi->value => $assessor,
            RoleName::Kapolsek->value => $assessor,
            RoleName::Wakapolsek->value => $assessor,
            RoleName::Kanit->value => $assessor,
            RoleName::Anggota->value => ['assessments.view-own'],
        ];

        foreach ($map as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
