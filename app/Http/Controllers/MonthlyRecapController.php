<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\OrganizationalUnit;
use App\Services\MonthlyScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyRecapController extends Controller
{
    public function __construct(private MonthlyScoreService $scores)
    {
    }

    public function index(Request $request): View
    {
        $periods = AssessmentPeriod::orderByDesc('start_date')->get();

        $period = $request->integer('period_id')
            ? $periods->firstWhere('id', $request->integer('period_id'))
            : ($this->scores->currentPeriod() ?? $periods->first());

        $period?->loadMissing('lockedBy');

        $people = $this->scores->scoredPersonnelQuery()
            ->with(['user', 'currentAssignment.unit'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w->where('nrp', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->when($request->integer('unit_id'), fn ($q, $unit) => $q->whereHas(
                'currentAssignment',
                fn ($a) => $a->whereIn('organizational_unit_id', OrganizationalUnit::descendantIdsOf($unit))
            ))
            ->orderBy('nrp')
            ->paginate(20)->withQueryString();

        $rows = $period
            ? $this->scores->compute($period, $people->getCollection()->pluck('id'))
            : collect();

        return view('recap.index', [
            'periods' => $periods,
            'period' => $period,
            'people' => $people,
            'rows' => $rows,
            'units' => OrganizationalUnit::orderBy('code')->get(),
        ]);
    }

    public function lock(Request $request, AssessmentPeriod $period): RedirectResponse
    {
        $count = $this->scores->lock($period, $request->user());

        return back()->with('success', "Rekap {$period->name} dikunci untuk {$count} personel.");
    }
}
