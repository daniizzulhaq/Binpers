<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentCriterionRequest;
use App\Models\AssessmentCriterion;
use App\Models\AssessmentPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssessmentCriterionController extends Controller
{
    public function store(AssessmentCriterionRequest $request, AssessmentPeriod $period): RedirectResponse
    {
        if ($blocked = $this->blocked($period)) {
            return $blocked;
        }

        $data = $request->validated();

        if ($this->exceedsTotal($period->totalWeight(), (float) $data['weight'])) {
            return back()->withInput()->with('error', 'Total bobot melebihi '.config('assessment.weight_total').'.');
        }

        $data['sort_order'] ??= ((int) $period->criteria()->max('sort_order')) + 1;
        $period->criteria()->create($data);

        return back()->with('success', 'Kriteria ditambahkan.');
    }

    public function update(AssessmentCriterionRequest $request, AssessmentCriterion $criterion): RedirectResponse
    {
        $period = $criterion->period;

        if ($blocked = $this->blocked($period)) {
            return $blocked;
        }

        $data = $request->validated();
        $others = $period->totalWeight() - (float) $criterion->weight;

        if ($this->exceedsTotal($others, (float) $data['weight'])) {
            return back()->withInput()->with('error', 'Total bobot melebihi '.config('assessment.weight_total').'.');
        }

        $data['sort_order'] ??= $criterion->sort_order;
        $criterion->update($data);

        return back()->with('success', 'Kriteria diperbarui.');
    }

    public function destroy(AssessmentCriterion $criterion): RedirectResponse
    {
        abort_unless(auth()->user()->can('criteria.manage'), 403);

        if ($blocked = $this->blocked($criterion->period)) {
            return $blocked;
        }

        $criterion->delete();

        return back()->with('success', 'Kriteria dihapus.');
    }

    /** Salin kriteria dari periode lain ke periode yang masih kosong. */
    public function copy(Request $request, AssessmentPeriod $period): RedirectResponse
    {
        abort_unless($request->user()->can('criteria.manage'), 403);

        $data = $request->validate([
            'source_period_id' => ['required', Rule::exists('assessment_periods', 'id'), Rule::notIn([$period->id])],
        ]);

        if ($blocked = $this->blocked($period)) {
            return $blocked;
        }

        if ($period->criteria()->exists()) {
            return back()->with('error', 'Periode ini sudah memiliki kriteria. Hapus dahulu bila ingin menyalin.');
        }

        DB::transaction(function () use ($period, $data) {
            AssessmentCriterion::where('assessment_period_id', $data['source_period_id'])
                ->orderBy('sort_order')->get()
                ->each(fn ($c) => $period->criteria()->create(
                    $c->only(['name', 'description', 'weight', 'min_score', 'max_score', 'sort_order'])
                ));
        });

        return back()->with('success', 'Kriteria berhasil disalin.');
    }

    private function blocked(AssessmentPeriod $period): ?RedirectResponse
    {
        return $period->isCriteriaLocked()
            ? back()->with('error', 'Kriteria terkunci karena periode sudah ditutup atau sudah memiliki penilaian.')
            : null;
    }

    private function exceedsTotal(float $current, float $add): bool
    {
        return $current + $add > (float) config('assessment.weight_total') + 0.001;
    }
}
