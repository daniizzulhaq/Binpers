<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Enums\NoteStatus;
use App\Enums\NoteType;
use App\Models\Assessment;
use App\Models\AssessmentCriterion;
use App\Models\AssessmentPeriod;
use App\Models\PerformanceNote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Nilai bulanan otomatis dari penilaian harian (Apresiasi/Teguran per kriteria).
 *
 * Setiap kriteria dimulai dari nilai dasar (config assessment.base_score, skala 0–100),
 * ditambah poin apresiasi dan dikurangi poin teguran pada kriteria itu, dibatasi 0–100.
 * Nilai akhir = rata-rata berbobot kriteria + poin catatan "Umum" (tanpa kriteria), dibatasi 0–100.
 */
class DailyScoreService
{
    public function __construct(private ScoreCalculator $calculator)
    {
    }

    public function baseScore(): float
    {
        return (float) config('assessment.base_score', 75);
    }

    /** Penilaian harian seorang penilai atas anggota pada periode ini. */
    public function notesQuery(AssessmentPeriod $period, int $assessorId, int|array $assesseeIds): Builder
    {
        return PerformanceNote::query()
            ->where('author_id', $assessorId)
            ->whereIn('subject_id', (array) $assesseeIds)
            ->whereIn('type', [NoteType::Positive->value, NoteType::Negative->value])
            ->whereBetween('incident_date', [$period->start_date->toDateString(), $period->end_date->toDateString()]);
    }

    /**
     * Ringkasan banyak anggota sekaligus, dikunci oleh assessee_id.
     *
     * @return Collection<int, array{
     *     criteria: array<int, array{score: float, points: int, positive: int, negative: int}>,
     *     general: array{points: int, positive: int, negative: int},
     *     count: int, pending: int, rejected: int, final: float, grade: string
     * }>
     */
    public function summarize(AssessmentPeriod $period, int $assessorId, Collection $assesseeIds): Collection
    {
        if ($assesseeIds->isEmpty()) {
            return collect();
        }

        $criteria = $period->criteria;
        $criterionIds = $criteria->pluck('id')->all();

        $rows = $this->notesQuery($period, $assessorId, $assesseeIds->all())
            ->toBase()
            // Hanya penilaian harian yang sudah disetujui atasan yang dihitung.
            ->selectRaw('subject_id, assessment_criterion_id,
                SUM(CASE WHEN status = ? THEN points ELSE 0 END) as points,
                SUM(CASE WHEN status = ? AND type = ? THEN 1 ELSE 0 END) as positive,
                SUM(CASE WHEN status = ? AND type = ? THEN 1 ELSE 0 END) as negative,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected', [
                NoteStatus::Approved->value,
                NoteStatus::Approved->value, NoteType::Positive->value,
                NoteStatus::Approved->value, NoteType::Negative->value,
                NoteStatus::Pending->value,
                NoteStatus::Rejected->value,
            ])
            ->groupBy('subject_id', 'assessment_criterion_id')
            ->get()
            ->groupBy('subject_id');

        return $assesseeIds->mapWithKeys(function ($id) use ($rows, $criteria, $criterionIds) {
            $general = ['points' => 0, 'positive' => 0, 'negative' => 0];
            $perCriterion = [];
            $pending = (int) $rows->get($id, collect())->sum('pending');
            $rejected = (int) $rows->get($id, collect())->sum('rejected');

            foreach ($rows->get($id, collect()) as $r) {
                $bucket = ['points' => (int) $r->points, 'positive' => (int) $r->positive, 'negative' => (int) $r->negative];

                // Catatan tanpa kriteria (atau kriteria periode lain) masuk "Umum".
                if ($r->assessment_criterion_id !== null && in_array((int) $r->assessment_criterion_id, $criterionIds, true)) {
                    $perCriterion[(int) $r->assessment_criterion_id] = $bucket;
                } else {
                    foreach ($bucket as $k => $v) {
                        $general[$k] += $v;
                    }
                }
            }

            $result = [];
            foreach ($criteria as $c) {
                $b = $perCriterion[$c->id] ?? ['points' => 0, 'positive' => 0, 'negative' => 0];
                $result[$c->id] = ['score' => $this->clamp($this->baseScore() + $b['points'])] + $b;
            }

            $final = $this->final($criteria, $result, $general['points']);
            $count = collect($result)->sum(fn ($b) => $b['positive'] + $b['negative']) + $general['positive'] + $general['negative'];

            return [$id => [
                'criteria' => $result,
                'general' => $general,
                'count' => $count,
                'pending' => $pending,
                'rejected' => $rejected,
                'final' => $final,
                'grade' => $this->calculator->gradeFor($final),
            ]];
        });
    }

    /**
     * Rekap penilai atas anggota ini untuk bulan tanggal tersebut sudah dikirim (atau final)?
     * Bila ya, penilaian harian di bulan itu dikunci sampai rekap dikembalikan untuk revisi.
     */
    public function recapSubmitted(?int $assessorId, int $assesseeId, $date): bool
    {
        $period = $assessorId ? AssessmentPeriod::forDate($date) : null;

        return $period !== null && Assessment::where([
            'assessment_period_id' => $period->id,
            'assessor_id' => $assessorId,
            'assessee_id' => $assesseeId,
        ])->whereNotIn('status', [AssessmentStatus::Draft->value, AssessmentStatus::Revision->value])->exists();
    }

    /** Skor kriteria 0–100 dikonversi ke skala kriteria (min–max) untuk disimpan sebagai detail. */
    public function toCriterionScale(AssessmentCriterion $criterion, float $normalized): float
    {
        $min = (float) $criterion->min_score;
        $max = (float) $criterion->max_score;

        return round($min + ($normalized / 100) * ($max - $min), 2);
    }

    /** Teks ringkas untuk kolom catatan detail, mis. "2 apresiasi, 1 teguran (+3 poin)". */
    public function describe(array $bucket): string
    {
        if ($bucket['positive'] + $bucket['negative'] === 0) {
            return 'Tidak ada penilaian harian; nilai dasar.';
        }

        $parts = array_filter([
            $bucket['positive'] ? "{$bucket['positive']} apresiasi" : null,
            $bucket['negative'] ? "{$bucket['negative']} teguran" : null,
        ]);
        $sign = $bucket['points'] > 0 ? '+' : '';

        return implode(', ', $parts)." ({$sign}{$bucket['points']} poin)";
    }

    public function clamp(float $score): float
    {
        return round(min(100, max(0, $score)), 2);
    }

    /** @param  array<int, array{score: float}>  $perCriterion */
    private function final(Collection $criteria, array $perCriterion, int $generalPoints): float
    {
        $totalWeight = (float) $criteria->sum('weight');

        $weighted = $totalWeight > 0
            ? $criteria->sum(fn ($c) => $perCriterion[$c->id]['score'] * (float) $c->weight) / $totalWeight
            : $this->baseScore();

        return $this->clamp($weighted + $generalPoints);
    }
}
