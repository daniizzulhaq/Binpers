<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Models\MonthlyScore;
use App\Models\PerformanceNote;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyScoreService
{
    public function __construct(private ScoreCalculator $calculator)
    {
    }

    /** Periode yang sedang berjalan, atau yang terakhir dimulai. */
    public function currentPeriod(): ?AssessmentPeriod
    {
        return AssessmentPeriod::whereDate('start_date', '<=', today())
            ->orderByDesc('start_date')
            ->first();
    }

    /** Personel yang direkap: aktif dan bukan HRD. */
    public function scoredPersonnelQuery(): Builder
    {
        return Personnel::query()
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->whereDoesntHave('user.roles', fn ($r) => $r->where('name', RoleName::Hrd->value));
    }

    /**
     * @return array{base: ?float, assessments: int, bonus: int, penalty: int, total: ?float, grade: ?string, locked: bool}
     */
    public function forPersonnel(AssessmentPeriod $period, Personnel $personnel): array
    {
        return $this->compute($period, collect([$personnel->id]))->get($personnel->id);
    }

    /** Rekap banyak personel sekaligus, dikunci oleh personnel_id. */
    public function compute(AssessmentPeriod $period, Collection $personnelIds): Collection
    {
        if ($personnelIds->isEmpty()) {
            return collect();
        }

        // Rekap terkunci: baca dari snapshot.
        if ($period->isRecapLocked()) {
            $rows = MonthlyScore::where('assessment_period_id', $period->id)
                ->whereIn('personnel_id', $personnelIds)
                ->get()->keyBy('personnel_id');

            return $personnelIds->mapWithKeys(fn ($id) => [
                $id => $rows->has($id) ? $this->fromSnapshot($rows->get($id)) : $this->make(null, 0, 0, 0, true),
            ]);
        }

        // Rata-rata penilaian final pada periode ini.
        $base = Assessment::where('assessment_period_id', $period->id)
            ->where('status', AssessmentStatus::Finalized->value)
            ->whereIn('assessee_id', $personnelIds)
            ->toBase()
            ->selectRaw('assessee_id, AVG(final_score) as avg_score, COUNT(*) as total')
            ->groupBy('assessee_id')
            ->get()->keyBy('assessee_id');

        // Poin catatan berdasarkan tanggal kejadian di dalam periode.
        $notes = PerformanceNote::whereIn('subject_id', $personnelIds)
            ->whereBetween('incident_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->toBase()
            ->selectRaw('subject_id,
                SUM(CASE WHEN points > 0 THEN points ELSE 0 END) as bonus,
                SUM(CASE WHEN points < 0 THEN -points ELSE 0 END) as penalty')
            ->groupBy('subject_id')
            ->get()->keyBy('subject_id');

        return $personnelIds->mapWithKeys(function ($id) use ($base, $notes) {
            $b = $base->get($id);
            $n = $notes->get($id);

            return [$id => $this->make(
                $b ? round((float) $b->avg_score, 2) : null,
                (int) ($b->total ?? 0),
                (int) ($n->bonus ?? 0),
                (int) ($n->penalty ?? 0),
                false,
            )];
        });
    }

    /** Kunci rekap: simpan nilai semua personel aktif secara permanen. */
    public function lock(AssessmentPeriod $period, User $actor): int
    {
        return DB::transaction(function () use ($period, $actor) {
            $period = AssessmentPeriod::lockForUpdate()->findOrFail($period->id);

            if ($period->isRecapLocked()) {
                throw ValidationException::withMessages(['period' => 'Rekap periode ini sudah dikunci.']);
            }

            $rows = $this->compute($period, $this->scoredPersonnelQuery()->pluck('id'));

            foreach ($rows as $personnelId => $r) {
                MonthlyScore::updateOrCreate(
                    ['assessment_period_id' => $period->id, 'personnel_id' => $personnelId],
                    [
                        'base_score' => $r['base'],
                        'assessment_count' => $r['assessments'],
                        'bonus' => $r['bonus'],
                        'penalty' => $r['penalty'],
                        'total' => $r['total'],
                        'grade' => $r['grade'],
                    ]
                );
            }

            $period->forceFill(['recap_locked_at' => now(), 'recap_locked_by' => $actor->id])->save();

            return $rows->count();
        });
    }

    // ---------------------------------------------------------------

    private function make(?float $base, int $count, int $bonus, int $penalty, bool $locked): array
    {
        $total = $base === null ? null : round(min(100, max(0, $base + $bonus - $penalty)), 2);

        return [
            'base' => $base,
            'assessments' => $count,
            'bonus' => $bonus,
            'penalty' => $penalty,
            'total' => $total,
            'grade' => $total === null ? null : $this->calculator->gradeFor($total),
            'locked' => $locked,
        ];
    }

    private function fromSnapshot(MonthlyScore $s): array
    {
        return [
            'base' => $s->base_score !== null ? (float) $s->base_score : null,
            'assessments' => $s->assessment_count,
            'bonus' => $s->bonus,
            'penalty' => $s->penalty,
            'total' => $s->total !== null ? (float) $s->total : null,
            'grade' => $s->grade,
            'locked' => true,
        ];
    }
}
