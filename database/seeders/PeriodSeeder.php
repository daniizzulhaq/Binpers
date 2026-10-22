<?php

namespace Database\Seeders;

use App\Enums\PeriodStatus;
use App\Models\AssessmentPeriod;
use Illuminate\Database\Seeder;

/** Periode bulan berjalan beserta kriteria awal; HRD dapat mengubahnya lewat aplikasi. */
class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $period = AssessmentPeriod::firstOrCreate(
            ['name' => $now->copy()->locale('id')->translatedFormat('F Y')],
            [
                'start_date' => $now->copy()->startOfMonth()->toDateString(),
                'end_date' => $now->copy()->endOfMonth()->toDateString(),
                'status' => PeriodStatus::Open,
            ]
        );

        if ($period->criteria()->exists()) {
            return;
        }

        // Bobot dapat diubah HRD; ini contoh, bukan ketentuan resmi.
        $criteria = [
            ['Disiplin dan Sikap Tampang', 25, 'Tepat waktu, tertib menjalankan dinas, seragam dan atribut lengkap, serta rambut dan penampilan rapi.'],
            ['Tanggung Jawab dan Hasil Kerja', 20, 'Menyelesaikan tugas sampai tuntas, tepat waktu, dengan hasil yang benar dan lengkap.'],
            ['Inisiatif dan Kepedulian', 15, 'Peka terhadap kebutuhan tugas, bertindak sesuai kewenangan tanpa selalu menunggu perintah, serta memberikan solusi.'],
            ['Etika dan Kerja Sama', 15, 'Sopan kepada pimpinan, rekan, dan bawahan; mau membantu; serta menerima koreksi dengan baik.'],
            ['Integritas', 10, 'Jujur dalam laporan dan pelaksanaan tugas, tidak meminta imbalan, serta tidak menyalahgunakan kewenangan.'],
            ['Pelayanan kepada Masyarakat', 15, 'Ramah, responsif, membantu masyarakat, dan tidak mempersulit pelayanan.'],
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
