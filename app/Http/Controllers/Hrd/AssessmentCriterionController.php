<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentCriterionRequest;
use App\Models\AssessmentCriterion;
use App\Models\AssessmentPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $data = $request->validated();
        $data['sort_order'] ??= $criterion->sort_order;

        // Terkunci: hanya nama, deskripsi, dan urutan yang diubah; bobot & skala tetap.
        if ($period->isCriteriaLocked()) {
            $criterion->update(array_intersect_key($data, array_flip(['name', 'description', 'sort_order'])));

            return back()->with('success', 'Nama/deskripsi/urutan kriteria diperbarui. Bobot dan skala tidak diubah karena sudah ada rekap yang dikirim atau rekap bulan dikunci.');
        }

        $others = $period->totalWeight() - (float) $criterion->weight;

        if ($this->exceedsTotal($others, (float) $data['weight'])) {
            return back()->withInput()->with('error', 'Total bobot melebihi '.config('assessment.weight_total').'.');
        }

        $criterion->update($data);

        return back()->with('success', 'Kriteria diperbarui.');
    }

    public function destroy(AssessmentCriterion $criterion): RedirectResponse
    {
        abort_unless(auth()->user()->can('criteria.manage'), 403);

        if ($blocked = $this->blocked($criterion->period)) {
            return $blocked;
        }

        // Detail rekap Draft/Revisi dibuang (dihitung ulang saat dikirim); penilaian harian
        // yang memakai kriteria ini dipindah ke "Umum" (FK null on delete).
        $files = [];
        $notes = $criterion->notes()->count();

        DB::transaction(function () use ($criterion, &$files) {
            $files = $criterion->details()->whereNotNull('evidence_path')->pluck('evidence_path')->all();
            $criterion->details()->delete();
            $criterion->delete();
        });

        Storage::disk('local')->delete($files);

        return back()->with('success', 'Kriteria dihapus.'
            .($notes ? " {$notes} penilaian harian yang memakai kriteria ini dipindah ke kategori Umum." : ''));
    }

    /** Ubah bobot (persentase) semua kriteria sekaligus; total harus tepat sesuai target. */
    public function updateWeights(Request $request, AssessmentPeriod $period): RedirectResponse
    {
        abort_unless($request->user()->can('criteria.manage'), 403);

        if ($blocked = $this->blocked($period)) {
            return $blocked;
        }

        $ids = $period->criteria()->pluck('id')->all();
        $data = $request->validate([
            'weights' => ['required', 'array'],
            'weights.*' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ], [], ['weights.*' => 'bobot']);

        $weights = array_intersect_key($data['weights'], array_flip($ids));
        $target = (float) config('assessment.weight_total');
        $total = array_sum(array_map('floatval', $weights));

        if (count($weights) !== count($ids)) {
            return back()->withInput()->with('error', 'Bobot semua kriteria wajib diisi.');
        }

        if (abs($total - $target) > 0.001) {
            return back()->withInput()->with('error', "Total bobot harus {$target}% (isian saat ini {$total}%).");
        }

        DB::transaction(function () use ($period, $weights) {
            foreach ($period->criteria as $criterion) {
                $criterion->update(['weight' => $weights[$criterion->id]]);
            }
        });

        return back()->with('success', 'Bobot kriteria diperbarui.');
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
            ? back()->with('error', 'Bobot/skala kriteria terkunci karena sudah ada rekap penilaian yang dikirim/final atau rekap bulan dikunci. Gunakan Reset Penilaian bila perlu.')
            : null;
    }

    private function exceedsTotal(float $current, float $add): bool
    {
        return $current + $add > (float) config('assessment.weight_total') + 0.001;
    }
}
