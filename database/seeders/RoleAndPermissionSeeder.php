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
            'assessments.review-structural',
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
        // Kepala bagian/satuan/seksi/polsek: menyetujui penilaian Kanit/Kasubag bawahannya.
        $structuralHead = [...$assessor, 'assessments.review-structural'];

        $map = [
            RoleName::Hrd->value => [
                'users.manage', 'personnel.manage', 'organization.manage', 'positions.manage',
                'authorizations.manage', 'assessment-rules.manage', 'periods.manage', 'criteria.manage',
                // HRD hanya memantau penilaian: tidak menyetujui maupun memfinalisasi.
                'rewards.manage', 'reports.view', 'analytics.view', 'audit-logs.view',
            ],
            RoleName::Kapolres->value => ['assessments.create', 'assessments.approve', 'reports.view', 'analytics.view'],
            RoleName::Wakapolres->value => ['assessments.create', 'assessments.approve', 'reports.view', 'analytics.view'],
            RoleName::Kabag->value => $structuralHead,
            RoleName::Kasat->value => $structuralHead,
            RoleName::KasiPengawas->value => $structuralHead,
            RoleName::Kasi->value => $structuralHead,
            RoleName::Kapolsek->value => $structuralHead,
            RoleName::Wakapolsek->value => $assessor,
            RoleName::Kanit->value => $assessor,
            RoleName::Kapolpos->value => $assessor,
            RoleName::Pamapta->value => $assessor,
        ];

        foreach (RoleName::members() as $member) {
            $map[$member] = ['assessments.view-own'];
        }

        foreach ($map as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
