<?php

namespace App\Http\Controllers\Hrd;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentPeriodRequest;
use App\Models\AssessmentPeriod;
use App\Services\PeriodResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentPeriodController extends Controller
{
    public function index(PeriodResetService $reset): View
    {
        $periods = AssessmentPeriod::withCount(['criteria', 'assessments'])
            ->orderByDesc('start_date')->paginate(15);

        return view('hrd.periods.index', [
            'periods' => $periods,
            'resetCounts' => $reset->countsAll(),
        ]);
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

    public function show(AssessmentPeriod $period, PeriodResetService $reset): View
    {
        $period->load(['criteria' => fn ($q) => $q->withCount('notes')])->loadCount('assessments');

        return view('hrd.periods.show', [
            'period' => $period,
            'totalWeight' => $period->totalWeight(),
            'weightTarget' => config('assessment.weight_total'),
            'locked' => $period->isCriteriaLocked(),
            'otherPeriods' => AssessmentPeriod::whereKeyNot($period->id)->orderByDesc('start_date')->get(),
            'resetCounts' => $reset->counts($period),
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

    /** Reset seluruh penilaian di semua periode agar sistem kosong kembali. */
    public function resetAll(Request $request, PeriodResetService $reset): RedirectResponse
    {
        $request->validate(
            ['confirmation' => ['required', 'in:RESET SEMUA']],
            ['confirmation.in' => 'Ketik RESET SEMUA (huruf kapital) untuk mengonfirmasi.'],
        );

        $c = $reset->resetAll();

        return redirect()->route('hrd.periods.index')->with('success',
            "Semua penilaian direset: {$c['assessments']} rekap penilaian, {$c['notes']} penilaian harian, "
            ."{$c['points']} transaksi poin, dan {$c['monthly']} rekap bulanan dihapus. "
            ."{$c['periods']} periode kembali ke Draft (kriteria tetap ada).");
    }

    /** Hapus seluruh penilaian periode ini agar kriteria & bobot dapat diubah kembali. */
    public function reset(Request $request, AssessmentPeriod $period, PeriodResetService $reset): RedirectResponse
    {
        $request->validate(
            ['confirmation' => ['required', 'in:RESET']],
            ['confirmation.in' => 'Ketik RESET (huruf kapital) untuk mengonfirmasi.'],
        );

        $c = $reset->reset($period);

        return redirect()->route('hrd.periods.show', $period)->with('success',
            "Penilaian periode direset: {$c['assessments']} rekap penilaian, {$c['notes']} penilaian harian, "
            ."{$c['points']} transaksi poin, dan {$c['monthly']} rekap bulanan dihapus. "
            .'Periode kembali ke Draft; ubah kriteria/bobot lalu buka kembali.');
    }
}
