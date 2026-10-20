<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentAuthorization;
use App\Models\AssessmentPeriod;
use App\Models\OrganizationalUnit;
use App\Models\Personnel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AnalyticsService
{
    /** Filter dari query string. Periode default: yang sedang dibuka, atau terbaru. */
    public function filters(Request $request): array
    {
        return [
            'period_id' => $request->integer('period_id') ?: AssessmentPeriod::query()
                ->orderByRaw("status = 'open' desc")->orderByDesc('start_date')->value('id'),
            'unit_id' => $request->integer('unit_id') ?: null,
            'position_id' => $request->integer('position_id') ?: null,
            'status' => AssessmentStatus::tryFrom((string) $request->input('status'))?->value,
        ];
    }

    public function baseQuery(array $f, bool $withStatus = true): Builder
    {
        return Assessment::query()
            ->when($f['period_id'] ?? null, fn ($q, $v) => $q->where('assessments.assessment_period_id', $v))
            ->when($f['unit_id'] ?? null, fn ($q, $v) => $q->whereIn('assessments.assessee_unit_id', OrganizationalUnit::descendantIdsOf($v)))
            ->when($f['position_id'] ?? null, fn ($q, $v) => $q->where('assessments.assessee_position_id', $v))
            ->when($withStatus && ($f['status'] ?? null), fn ($q) => $q->where('assessments.status', $f['status']));
    }

    public function dashboard(array $f): array
    {
        $base = $this->baseQuery($f, false);

        $counts = (clone $base)->toBase()
            ->selectRaw('assessments.status as status, COUNT(*) as total')
            ->groupBy('assessments.status')
            ->pluck('total', 'status');

        $expected = AssessmentAuthorization::effective()->count();
        $done = (int) collect(['submitted', 'approved', 'finalized'])->sum(fn ($s) => $counts[$s] ?? 0);

        return [
            'summary' => [
                'personnel' => Personnel::whereHas('user', fn ($q) => $q->where('is_active', true))->count(),
                'assessments' => (int) $counts->sum(),
                'finalized' => (int) ($counts['finalized'] ?? 0),
                'pending' => (int) ($counts['submitted'] ?? 0),
                'average' => round((float) (clone $base)->where('assessments.status', 'finalized')->avg('assessments.final_score'), 2),
                'progress' => $expected > 0 ? min(100, round($done / $expected * 100, 1)) : 0,
            ],
            'statusCounts' => $counts,
            'distribution' => $this->distribution($base),
            'unitRecap' => $this->unitRecap($base),
            'trend' => $this->trend($f),
            'pending' => (clone $base)
                ->with(['assessor.user', 'assessee.user', 'assesseeUnit', 'period'])
                ->where('assessments.status', 'submitted')
                ->latest('assessments.submitted_at')->limit(10)->get(),
        ];
    }

    private function distribution(Builder $base)
    {
        return (clone $base)->toBase()
            ->where('assessments.status', 'finalized')->whereNotNull('assessments.grade')
            ->selectRaw('assessments.grade as grade, COUNT(*) as total')
            ->groupBy('assessments.grade')
            ->pluck('total', 'grade');
    }

    private function unitRecap(Builder $base)
    {
        return (clone $base)->toBase()
            ->join('organizational_units as u', 'u.id', '=', 'assessments.assessee_unit_id')
            ->selectRaw("u.id, u.name, COUNT(*) as total,
                SUM(assessments.status = 'finalized') as finalized,
                AVG(CASE WHEN assessments.status = 'finalized' THEN assessments.final_score END) as avg_score")
            ->groupBy('u.id', 'u.name')
            ->orderBy('u.name')
            ->get();
    }

    /** Rata-rata nilai final per periode (12 periode terakhir), tanpa filter periode. */
    private function trend(array $f)
    {
        return $this->baseQuery(['unit_id' => $f['unit_id'] ?? null, 'position_id' => $f['position_id'] ?? null], false)
            ->toBase()
            ->join('assessment_periods as p', 'p.id', '=', 'assessments.assessment_period_id')
            ->where('assessments.status', 'finalized')
            ->selectRaw('p.id, p.name, p.start_date, AVG(assessments.final_score) as avg_score, COUNT(*) as total')
            ->groupBy('p.id', 'p.name', 'p.start_date')
            ->orderByDesc('p.start_date')
            ->limit(12)->get()->reverse()->values();
    }
}
