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
                'nrp' => $nrp,
                'rank' => $rank,
                'gender' => 'L',
                'join_date' => '2015-01-01',
            ]);

            if (! $personnel->currentAssignment) {
                $personnel->assignments()->create([
                    'organizational_unit_id' => $unit->id,
                    'position_id' => $pos[$position]->id,
                    'start_date' => '2024-01-01',
                ]);
            }
        }

        // ---- Kewenangan tambahan (manual) ----
        // Hak menilai utama sudah otomatis dari aturan role (AssessmentRuleSeeder).
        // Contoh pengecualian: Kanit diberi kewenangan menilai anggota di Bagian Operasional (lintas unit).
        $personnelId = fn (string $username) => Personnel::whereHas('user', fn ($q) => $q->where('username', $username))->value('id');

        AssessmentAuthorization::updateOrCreate(
            ['assessor_id' => $personnelId('kanit'), 'assessee_id' => $personnelId('anggota')],
            ['is_active' => true, 'notes' => 'Data demo: kewenangan tambahan lintas unit']
        );

        // ---- Periode bulan berjalan + kriteria ----
        $now = now();

        $period = AssessmentPeriod::firstOrCreate(
            ['name' => $now->copy()->locale('id')->translatedFormat('F Y')],
            [
                'start_date' => $now->copy()->startOfMonth()->toDateString(),
                'end_date' => $now->copy()->endOfMonth()->toDateString(),
                'status' => PeriodStatus::Open,
                'description' => 'Periode demo.',
            ]
        );

        if ($period->criteria()->doesntExist()) {
            // Bobot dapat diubah HRD; ini contoh, bukan ketentuan resmi.
            $criteria = [
                ['Disiplin dan Sikap Tampang', 25, 'Tepat waktu, tertib menjalankan dinas, seragam dan atribut lengkap, serta rambut dan penampilan rapi.'],
                ['Tanggung Jawab dan Hasil Kerja', 20, 'Menyelesaikan tugas sampai tuntas, tepat waktu, dengan hasil yang benar dan lengkap.'],
                ['Inisiatif dan Kepedulian', 15, 'Peka terhadap kebutuhan tugas, bertindak sesuai kewenangan tanpa selalu menunggu perintah, serta memberikan solusi.'],
                ['Etika dan Kerja Sama', 15, 'Sopan kepada pimpinan, rekan, dan bawahan; mau membantu; serta menerima koreksi dengan baik.'],
                ['Pelayanan kepada Masyarakat', 15, 'Ramah, responsif, membantu masyarakat, dan tidak mempersulit pelayanan.'],
                ['Integritas', 10, 'Jujur dalam laporan dan pelaksanaan tugas, tidak meminta imbalan, serta tidak menyalahgunakan kewenangan.'],
            ];

            foreach ($criteria as $i => [$name, $weight, $indicator]) {
                $period->criteria()->create([
                    'name' => $name,
                    'description' => $indicator,
                    'weight' => $weight,
                    'min_score' => 0,
                    'max_score' => 100,
                    'sort_order' => $i + 1,
                ]);
            }
        }
    }

    private function unit(string $code, string $name, UnitType $type, ?OrganizationalUnit $parent = null): OrganizationalUnit
    {
        return OrganizationalUnit::updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'parent_id' => $parent?->id, 'is_active' => true]
        );
    }
}
