<?php

namespace Database\Seeders;

use App\Models\AssessmentRule;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentRuleSeeder extends Seeder
{
    public function run(): void
    {
        $kanitProvost = Position::firstOrCreate(['name' => 'Kanit Provost'], ['is_active' => true]);

        $leaderTargets = ['wakapolres', 'kabag', 'kasat', 'kasi_pengawas', 'kasi', 'kapolsek', 'kanit', 'anggota'];

        // [role penilai, cakupan, role yang dinilai, wajib?, jabatan dikecualikan, keterangan]
        $rules = [
            ['kapolres', 'polres', $leaderTargets, false, null, 'Dapat menilai Wakapolres, Kabag, Kasat, Kasi, Kapolsek, Kanit, dan anggota.'],
            ['wakapolres', 'polres', $leaderTargets, false, null, 'Dapat menilai Wakapolres, Kabag, Kasat, Kasi, Kapolsek, Kanit, dan anggota.'],

            ['kabag', 'unit', ['*'], true, null, 'Wajib menilai personel di bagiannya.'],
            ['kabag', 'polres', ['kasat', 'kasi_pengawas', 'kasi', 'kapolsek', 'kanit', 'anggota'], false, null, 'Dapat menilai Kasat, Kasi, Kapolsek, Kanit, dan anggota di luar bagiannya.'],

            ['kasat', 'unit', ['*'], true, null, 'Wajib menilai personel di satuannya.'],
            ['kasat', 'polres', ['kapolsek', 'kanit', 'anggota'], false, null, 'Dapat menilai Kapolsek, Kanit, dan anggota di luar satuannya.'],

            ['kasi_pengawas', 'unit', ['*'], true, null, 'Wajib menilai personel di seksinya.'],
            ['kasi_pengawas', 'polres', ['kabag', 'kasat', 'kasi_pengawas', 'kasi', 'kapolsek', 'kanit', 'anggota'], false, null, 'Dapat menilai Kabag, Kasat, Kasi, Kapolsek, Kanit, dan anggota di luar seksinya.'],

            ['kasi', 'unit', ['*'], true, null, 'Wajib menilai personel di seksinya.'],
            ['kasi', 'polres', ['anggota'], false, null, 'Dapat menilai anggota lain.'],

            ['kapolsek', 'unit', ['kanit', 'anggota'], true, [$kanitProvost->id], 'Wajib menilai Kasium, Ka SPKT, para Kanit (kecuali Kanit Provost), dan anggota polsek.'],
            ['wakapolsek', 'unit', ['kanit', 'anggota'], true, null, 'Wajib menilai Kasium, Ka SPKT, seluruh Kanit, dan seluruh anggota polsek.'],

            ['kanit', 'unit', ['anggota'], true, null, 'Wajib menilai anggota di unitnya.'],
        ];

        DB::transaction(function () use ($rules) {
            AssessmentRule::query()->delete();

            foreach ($rules as [$role, $scope, $targets, $mandatory, $excluded, $desc]) {
                AssessmentRule::create([
                    'assessor_role' => $role,
                    'scope' => $scope,
                    'target_roles' => $targets,
                    'excluded_position_ids' => $excluded,
                    'is_mandatory' => $mandatory,
                    'is_active' => true,
                    'description' => $desc,
                ]);
            }
        });
    }
}
