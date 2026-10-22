<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hitung ulang predikat yang sudah tersimpan dengan ambang baru:
 * 85–100 Sangat Baik, 70–<85 Baik, 60–<70 Cukup, 0–<60 Perlu Pembinaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->recalculate([85, 70, 60], ['Sangat Baik', 'Baik', 'Cukup', 'Perlu Pembinaan']);
    }

    public function down(): void
    {
        $this->recalculate([90, 75, 60], ['Sangat Baik', 'Baik', 'Cukup', 'Kurang']);
    }

    private function recalculate(array $mins, array $labels): void
    {
        foreach (['assessments' => 'final_score', 'monthly_scores' => 'total'] as $table => $column) {
            DB::table($table)->whereNotNull($column)->update([
                'grade' => DB::raw("CASE
                    WHEN {$column} >= {$mins[0]} THEN '{$labels[0]}'
                    WHEN {$column} >= {$mins[1]} THEN '{$labels[1]}'
                    WHEN {$column} >= {$mins[2]} THEN '{$labels[2]}'
                    ELSE '{$labels[3]}' END"),
            ]);
        }
    }
};
