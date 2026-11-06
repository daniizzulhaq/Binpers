<?php

namespace App\Services;

use App\Enums\NoteType;
use App\Enums\PeriodStatus;
use App\Enums\PointType;
use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\AuditLog;
use App\Models\MonthlyScore;
use App\Models\PerformanceNote;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Reset penilaian satu periode: hapus rekap penilaian (beserta detail, riwayat persetujuan,
 * dan poin perolehannya), snapshot rekap bulanan, serta penilaian harian (Apresiasi/Teguran)
 * di periode itu. Riwayat Pelanggaran tidak ikut dihapus. Periode kembali ke status Draft
 * sehingga kriteria dan bobot dapat diubah lagi.
 */
class PeriodResetService
{
    /** @return array{assessments: int, notes: int, points: int, monthly: int} */
    public function counts(AssessmentPeriod $period): array
    {
        return [
            'assessments' => $period->assessments()->count(),
            'notes' => $this->notesQuery($period)->count(),
            'points' => $this->pointsQuery($period)->count(),
            'monthly' => $period->monthlyScores()->count(),
        ];
    }

    /** @return array{assessments: int, notes: int, points: int, monthly: int} */
    public function reset(AssessmentPeriod $period): array
    {
        $files = [];

        $counts = DB::transaction(function () use ($period, &$files) {
            $period = AssessmentPeriod::lockForUpdate()->findOrFail($period->id);
            $counts = $this->counts($period);

            $assessmentIds = $period->assessments()->pluck('id');

            $files = array_merge(
                AssessmentDetail::whereIn('assessment_id', $assessmentIds)->whereNotNull('evidence_path')->pluck('evidence_path')->all(),
                $this->notesQuery($period)->whereNotNull('image_path')->pluck('image_path')->all(),
            );

            $this->pointsQuery($period)->delete();
            // Detail & riwayat persetujuan ikut terhapus (cascade).
            Assessment::whereIn('id', $assessmentIds)->delete();
            $this->notesQuery($period)->delete();
            MonthlyScore::where('assessment_period_id', $period->id)->delete();

            $old = $period->only(['status', 'recap_locked_at', 'recap_locked_by']);
            $period->forceFill([
                'status' => PeriodStatus::Draft,
                'recap_locked_at' => null,
                'recap_locked_by' => null,
            ])->saveQuietly();

            // Hapus massal tidak memicu audit per baris; catat satu entri ringkasan.
            AuditLog::record('reset', $period, $old + ['deleted' => $counts], [
                'status' => PeriodStatus::Draft->value,
                'recap_locked_at' => null,
            ]);

            return $counts;
        });

        // Berkas dihapus setelah transaksi berhasil.
        foreach (array_unique($files) as $path) {
            Storage::disk('local')->delete($path);
        }

        return $counts;
    }

    /** @return array{assessments: int, notes: int, points: int, monthly: int, periods: int} */
    public function countsAll(): array
    {
        return [
            'assessments' => Assessment::count(),
            'notes' => $this->allNotesQuery()->count(),
            'points' => $this->allPointsQuery()->count(),
            'monthly' => MonthlyScore::count(),
            'periods' => AssessmentPeriod::count(),
        ];
    }

    /**
     * Reset SEMUA penilaian di semua periode: seluruh rekap penilaian, poin perolehan dari rekap,
     * rekap bulanan, dan seluruh penilaian harian (Apresiasi/Teguran). Periode & kriteria tetap ada,
     * semuanya kembali ke Draft. Riwayat Pelanggaran, penyesuaian poin manual, dan
     * pemberian apresiasi tidak dihapus.
     *
     * @return array{assessments: int, notes: int, points: int, monthly: int, periods: int}
     */
    public function resetAll(): array
    {
        $files = [];

        $counts = DB::transaction(function () use (&$files) {
            AssessmentPeriod::lockForUpdate()->get();
            $counts = $this->countsAll();

            $files = array_merge(
                AssessmentDetail::whereNotNull('evidence_path')->pluck('evidence_path')->all(),
                $this->allNotesQuery()->whereNotNull('image_path')->pluck('image_path')->all(),
            );

            $this->allPointsQuery()->delete();
            Assessment::query()->delete();
            $this->allNotesQuery()->delete();
            MonthlyScore::query()->delete();
            // Notifikasi penilaian lama sudah tidak punya tujuan.
            DB::table('notifications')->whereIn('type', [
                \App\Notifications\AssessmentStatusNotification::class,
                \App\Notifications\NoteReviewNotification::class,
            ])->delete();
            AssessmentPeriod::query()->update([
                'status' => PeriodStatus::Draft->value,
                'recap_locked_at' => null,
                'recap_locked_by' => null,
            ]);

            AuditLog::record('reset-all', new AssessmentPeriod, ['deleted' => $counts], [
                'status' => PeriodStatus::Draft->value,
            ]);

            return $counts;
        });

        foreach (array_unique($files) as $path) {
            Storage::disk('local')->delete($path);
        }

        return $counts;
    }

    private function allNotesQuery()
    {
        return PerformanceNote::whereIn('type', [NoteType::Positive->value, NoteType::Negative->value]);
    }

    private function allPointsQuery()
    {
        return PointTransaction::where('type', PointType::Earned->value)->whereNotNull('assessment_id');
    }

    /** Penilaian harian pada rentang periode atau yang menunjuk kriteria periode ini. */
    private function notesQuery(AssessmentPeriod $period)
    {
        return PerformanceNote::query()
            ->whereIn('type', [NoteType::Positive->value, NoteType::Negative->value])
            ->where(fn ($q) => $q
                ->whereBetween('incident_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
                ->orWhereIn('assessment_criterion_id', $period->criteria()->select('id')));
    }

    private function pointsQuery(AssessmentPeriod $period)
    {
        return PointTransaction::where('type', PointType::Earned->value)
            ->whereIn('assessment_id', $period->assessments()->select('id'));
    }
}
