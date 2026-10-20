<?php

namespace App\Http\Controllers\Hrd;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentPeriodRequest;
use App\Models\AssessmentPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentPeriodController extends Controller
{
    public function index(): View
    {
        $periods = AssessmentPeriod::withCount(['criteria', 'assessments'])
            ->orderByDesc('start_date')->paginate(15);

        return view('hrd.periods.index', compact('periods'));
    }

    public function create(): View
    {
        return view('hrd.periods.form', ['period' => null]);
    }

    public function store(AssessmentPeriodRequest $request): RedirectResponse
    {
        $period = AssessmentPeriod::create($request->validated() + ['status' => PeriodStatus::Draft]);

        return redirect()->route('hrd.periods.show', $period)
            ->with('success', 'Periode dibuat. Lanjutkan dengan mengatur kriteria dan bobot.');
    }

    public function show(AssessmentPeriod $period): View
    {
        $period->load('criteria')->loadCount('assessments');

        return view('hrd.periods.show', [
            'period' => $period,
            'totalWeight' => $period->totalWeight(),
            'weightTarget' => config('assessment.weight_total'),
            'locked' => $period->isCriteriaLocked(),
            'otherPeriods' => AssessmentPeriod::whereKeyNot($period->id)->orderByDesc('start_date')->get(),
        ]);
    }

    public function edit(AssessmentPeriod $period): View
    {
        return view('hrd.periods.form', compact('period'));
    }

    public function update(AssessmentPeriodRequest $request, AssessmentPeriod $period): RedirectResponse
    {
        $period->update($request->validated());

        return redirect()->route('hrd.periods.show', $period)->with('success', 'Periode diperbarui.');
    }

    public function destroy(AssessmentPeriod $period): RedirectResponse
    {
        if ($period->status !== PeriodStatus::Draft || $period->assessments()->exists()) {
            return back()->with('error', 'Hanya periode draft tanpa penilaian yang dapat dihapus.');
        }

        $period->delete();

        return redirect()->route('hrd.periods.index')->with('success', 'Periode dihapus.');
    }

    public function open(AssessmentPeriod $period): RedirectResponse
    {
        $target = (float) config('assessment.weight_total');
        $total = $period->totalWeight();

        if ($period->criteria()->doesntExist()) {
            return back()->with('error', 'Tambahkan kriteria terlebih dahulu.');
        }

        if (abs($total - $target) > 0.001) {
            return back()->with('error', "Total bobot kriteria harus {$target} (saat ini {$total}).");
        }

        if (AssessmentPeriod::open()->whereKeyNot($period->id)->exists()) {
            return back()->with('error', 'Masih ada periode lain yang dibuka. Tutup terlebih dahulu.');
        }

        $period->update(['status' => PeriodStatus::Open]);

        return back()->with('success', 'Periode dibuka. Penilai kini dapat mengisi penilaian.');
    }

    public function close(AssessmentPeriod $period): RedirectResponse
    {
        $period->update(['status' => PeriodStatus::Closed]);

        return back()->with('success', 'Periode ditutup.');
    }
}
