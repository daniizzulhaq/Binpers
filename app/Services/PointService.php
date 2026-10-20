<?php

namespace App\Services;

use App\Enums\PointType;
use App\Models\Assessment;
use App\Models\Personnel;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointService
{
    /** Poin yang dihitung ke total: perolehan dan penyesuaian. Pemberian apresiasi bernilai 0. */
    private function counted(): array
    {
        return [PointType::Earned->value, PointType::Adjustment->value];
    }

    public function pointsForGrade(?string $grade): int
    {
        return (int) (config('assessment.points.by_grade')[$grade] ?? 0);
    }

    public function total(Personnel $personnel): int
    {
        return (int) $personnel->pointTransactions()->whereIn('type', $this->counted())->sum('points');
    }

    /** Poin dari satu penilaian final. Idempotent: satu penilaian hanya sekali. */
    public function awardForAssessment(Assessment $assessment, User $actor): ?PointTransaction
    {
        $points = $this->pointsForGrade($assessment->grade);

        if ($points <= 0) {
            return null;
        }

        $exists = PointTransaction::where('assessment_id', $assessment->id)
            ->where('type', PointType::Earned->value)->exists();

        if ($exists) {
            return null;
        }

        return PointTransaction::create([
            'personnel_id' => $assessment->assessee_id,
            'assessment_id' => $assessment->id,
            'type' => PointType::Earned,
            'points' => $points,
            'description' => "Penilaian {$assessment->period->name} ({$assessment->grade})",
            'created_by' => $actor->id,
        ]);
    }

    /** Personel aktif yang memenuhi syarat poin dan belum menerima apresiasi ini. */
    public function eligibleQuery(Reward $reward): Builder
    {
        $counted = $this->counted();

        return Personnel::query()
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->withSum(['pointTransactions as total_points' => fn ($q) => $q->whereIn('type', $counted)], 'points')
            ->whereRaw(
                '(select coalesce(sum(pt.points), 0) from point_transactions pt
                  where pt.personnel_id = personnel.id and pt.type in (?, ?)) >= ?',
                [...$counted, $reward->min_points]
            )
            ->whereDoesntHave('pointTransactions', fn ($q) => $q
                ->where('reward_id', $reward->id)
                ->where('type', PointType::Redeemed->value));
    }

    public function grant(Reward $reward, Personnel $personnel, User $actor, ?string $notes = null): PointTransaction
    {
        return DB::transaction(function () use ($reward, $personnel, $actor, $notes) {
            // Kunci baris personel agar dua pemberian bersamaan tidak lolos keduanya.
            Personnel::whereKey($personnel->id)->lockForUpdate()->first();

            if (! $reward->is_active) {
                throw ValidationException::withMessages(['reward' => 'Apresiasi ini tidak aktif.']);
            }

            if (! $this->eligibleQuery($reward)->whereKey($personnel->id)->exists()) {
                throw ValidationException::withMessages([
                    'reward' => 'Anggota belum memenuhi syarat poin, tidak aktif, atau apresiasi ini sudah pernah diberikan.',
                ]);
            }

            return PointTransaction::create([
                'personnel_id' => $personnel->id,
                'reward_id' => $reward->id,
                'type' => PointType::Redeemed,
                'points' => 0,
                'description' => trim("Apresiasi: {$reward->name}. ".($notes ?? '')),
                'created_by' => $actor->id,
            ]);
        });
    }

    public function adjust(Personnel $personnel, int $points, string $description, User $actor): PointTransaction
    {
        return DB::transaction(function () use ($personnel, $points, $description, $actor) {
            Personnel::whereKey($personnel->id)->lockForUpdate()->first();

            if ($this->total($personnel) + $points < 0) {
                throw ValidationException::withMessages(['points' => 'Penyesuaian ini membuat total poin menjadi negatif.']);
            }

            return PointTransaction::create([
                'personnel_id' => $personnel->id,
                'type' => PointType::Adjustment,
                'points' => $points,
                'description' => $description,
                'created_by' => $actor->id,
            ]);
        });
    }
}
