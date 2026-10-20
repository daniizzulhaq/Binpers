<?php

namespace Database\Seeders;

use App\Enums\PeriodStatus;
use App\Enums\RoleName;
use App\Enums\UnitType;
use App\Models\AssessmentAuthorization;
use App\Models\AssessmentPeriod;
use App\Models\OrganizationalUnit;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Struktur organisasi ----
        $polres = $this->unit('POLRES', 'Polres Demo', UnitType::Polres);
        $bagops = $this->unit('BAGOPS', 'Bagian Operasional', UnitType::Bagian, $polres);
        $satreskrim = $this->unit('SATRESKRIM', 'Satuan Reserse Kriminal', UnitType::Satuan, $polres);
        $sium = $this->unit('SIUM', 'Seksi Umum', UnitType::Seksi, $polres);
        $unitIdik = $this->unit('UNITIDIK1', 'Unit Idik I', UnitType::Unit, $satreskrim);

        // ---- Jabatan ----
        $pos = [];
        foreach (['Kapolres', 'Wakapolres', 'Kepala Bagian', 'Kepala Satuan', 'Kepala Seksi', 'Kepala Unit', 'Anggota'] as $name) {
            $pos[$name] = Position::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        // ---- Akun tambahan agar ada lebih dari satu anggota ----
        foreach (['anggota2' => 'Anggota Demo 2', 'anggota3' => 'Anggota Demo 3'] as $username => $name) {
            $user = User::updateOrCreate(['username' => $username], [
                'name' => $name,
                'email' => $username.'@polres.test',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
            $user->syncRoles([RoleName::Anggota->value]);
        }

        // ---- Personel + penempatan ----
        $people = [
            ['kapolres', '75010001', 'Kombes Pol', $polres, 'Kapolres'],
            ['wakapolres', '77010002', 'AKBP', $polres, 'Wakapolres'],
            ['kabag', '80010003', 'Kompol', $bagops, 'Kepala Bagian'],
            ['kasat', '81010004', 'AKP', $satreskrim, 'Kepala Satuan'],
            ['kasi', '85010005', 'Iptu', $sium, 'Kepala Seksi'],
            ['kanit', '88010006', 'Ipda', $unitIdik, 'Kepala Unit'],
            ['anggota', '95010007', 'Bripka', $bagops, 'Anggota'],
            ['anggota2', '96010008', 'Briptu', $unitIdik, 'Anggota'],
            ['anggota3', '97010009', 'Bripda', $sium, 'Anggota'],
        ];

        foreach ($people as [$username, $nrp, $rank, $unit, $position]) {
            $user = User::where('username', $username)->firstOrFail();
            $personnel = Personnel::updateOrCreate(['user_id' => $user->id], [
                'nrp' => $nrp, 'rank' => $rank, 'gender' => 'L', 'join_date' => '2015-01-01',
            ]);

            if (! $personnel->currentAssignment) {
                $personnel->assignments()->create([
                    'organizational_unit_id' => $unit->id,
                    'position_id' => $pos[$position]->id,
                    'start_date' => '2024-01-01',
                ]);
            }
        }

        // ---- Kewenangan menilai (kanit menilai lintas unit: anggota di BAGOPS) ----
        $pairs = [
            'kabag' => ['anggota'],
            'kasat' => ['anggota2', 'kanit'],
            'kasi' => ['anggota3'],
            'kanit' => ['anggota2', 'anggota'],
        ];
        $personnelId = fn (string $username) => Personnel::whereHas('user', fn ($q) => $q->where('username', $username))->value('id');

        foreach ($pairs as $assessor => $assessees) {
            foreach ($assessees as $assessee) {
                AssessmentAuthorization::updateOrCreate(
                    ['assessor_id' => $personnelId($assessor), 'assessee_id' => $personnelId($assessee)],
                    ['is_active' => true, 'notes' => 'Data demo']
                );
            }
        }

        // ---- Periode (kuartal berjalan) + kriteria contoh ----
        $now = now();
        $period = AssessmentPeriod::firstOrCreate(
            ['name' => "Triwulan {$now->quarter} {$now->year}"],
            [
                'start_date' => $now->copy()->startOfQuarter()->toDateString(),
                'end_date' => $now->copy()->endOfQuarter()->toDateString(),
                'status' => PeriodStatus::Open,
                'description' => 'Periode demo.',
            ]
        );

        if ($period->criteria()->doesntExist()) {
            $criteria = [
                ['Disiplin', 25], ['Tanggung Jawab', 20], ['Kerja Sama', 15],
                ['Inisiatif', 15], ['Pelayanan Masyarakat', 15], ['Integritas', 10],
            ];
            foreach ($criteria as $i => [$name, $weight]) {
                $period->criteria()->create([
                    'name' => $name,
                    'description' => 'Contoh kriteria, bukan ketentuan resmi.',
                    'weight' => $weight,
                    'min_score' => 0,
                    'max_score' => 100,
                    'sort_order' => $i + 1,
                ]);
            }
        }

        // ---- Reward contoh ----
        Reward::firstOrCreate(['name' => 'Piagam Penghargaan'], ['min_points' => 100, 'description' => 'Contoh apresiasi.', 'is_active' => true]);
        Reward::firstOrCreate(['name' => 'Penghargaan Anggota Terbaik'], ['min_points' => 300, 'description' => 'Contoh apresiasi.', 'is_active' => true]);
    }

    private function unit(string $code, string $name, UnitType $type, ?OrganizationalUnit $parent = null): OrganizationalUnit
    {
        return OrganizationalUnit::updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'parent_id' => $parent?->id, 'is_active' => true]
        );
    }
}
