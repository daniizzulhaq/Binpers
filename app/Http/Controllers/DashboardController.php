<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentAuthorization;
use App\Models\AssessmentPeriod;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Services\AnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Mengarahkan pengguna ke beranda sesuai role-nya. */
    public function index(Request $request): RedirectResponse
    {
        $route = $request->user()->homeRoute();

        abort_if($route === null, 403, 'Akun Anda belum memiliki role. Hubungi HRD.');

        return redirect()->route($route);
    }

    public function hrd(Request $request, AnalyticsService $analytics): View
    {
        return view('dashboards.hrd', $this->analyticsData($request, $analytics));
    }

    public function leader(Request $request, AnalyticsService $analytics): View
    {
        return view('dashboards.leader', $this->analyticsData($request, $analytics));
    }

    /** Beranda penilai: ringkasan tugas pada periode yang sedang dibuka + hasil pribadinya. */
    public function assessor(Request $request): View
    {
        $me = $request->user()->personnel;
        $period = AssessmentPeriod::open()->first();

        $assigned = $me
            ? AssessmentAuthorization::effective()->where('assessor_id', $me->id)->count()
            : 0;

        $mine = ($me && $period)
            ? Assessment::with('assessee.user')
                ->where('assessment_period_id', $period->id)
                ->where('assessor_id', $me->id)->get()
            : collect();

        $latest = $me
            ? Assessment::with('period:id,name')
                ->select(['id', 'assessment_period_id', 'final_score', 'grade', 'finalized_at'])
                ->where('assessee_id', $me->id)
                ->where('status', AssessmentStatus::Finalized->value)
                ->latest('finalized_at')->first()
            : null;

        return view('dashboards.assessor', [
            'me' => $me,
            'period' => $period,
            'assigned' => $assigned,
            'counts' => $mine->groupBy(fn ($a) => $a->status->value)->map->count(),
            'notStarted' => max(0, $assigned - $mine->count()),
            'revisions' => $mine->filter(fn ($a) => $a->status === AssessmentStatus::Revision)->values(),
            'latest' => $latest,
        ]);
    }

    /** Beranda anggota: hanya hasil final miliknya sendiri. */
    public function member(Request $request): View
    {
        $me = $request->user()->personnel;

        $latest = $me
            ? Assessment::with('period:id,name')
                ->select(['id', 'assessment_period_id', 'final_score', 'grade', 'finalized_at'])
                ->where('assessee_id', $me->id)
                ->where('status', AssessmentStatus::Finalized->value)
                ->latest('finalized_at')->first()
            : null;

        return view('dashboards.member', [
            'me' => $me?->load('currentAssignment.unit', 'currentAssignment.position'),
            'latest' => $latest,
            'points' => $me?->totalPoints() ?? 0,
        ]);
    }

    private function analyticsData(Request $request, AnalyticsService $analytics): array
    {
        $filters = $analytics->filters($request);

        return [
            'filters' => $filters,
            'data' => $analytics->dashboard($filters),
            'periods' => AssessmentPeriod::orderByDesc('start_date')->get(),
            'units' => OrganizationalUnit::orderBy('code')->get(),
            'positions' => Position::orderBy('name')->get(),
        ];
    }
}
