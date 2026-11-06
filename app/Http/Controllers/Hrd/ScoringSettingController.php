<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\AssessmentSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Ambang predikat nilai dan nilai dasar penilaian harian. */
class ScoringSettingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $labels = collect(config('assessment.grades'))->pluck('label');
        $lowest = collect(config('assessment.grades'))->sortBy('min')->first()['label'];

        $rules = ['base_score' => ['required', 'numeric', 'min:0', 'max:100']];
        foreach ($labels as $i => $label) {
            $rules["grades.{$i}"] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        $data = $request->validate($rules, [], ['grades.*' => 'nilai minimum predikat', 'base_score' => 'nilai dasar']);

        // Predikat terendah selalu mulai dari 0; urutan predikat harus tetap menurun.
        $mins = [];
        foreach ($labels as $i => $label) {
            $mins[$label] = $label === $lowest ? 0.0 : (float) $data['grades'][$i];
        }

        $values = array_values($mins);
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i] >= $values[$i - 1]) {
                return back()->withInput()->with('error', 'Nilai minimum predikat harus menurun: '.implode(' > ', $labels->all()).'.');
            }
        }

        DB::transaction(function () use ($mins, $data) {
            AssessmentSetting::setValue(AssessmentSetting::GRADES, $mins);
            AssessmentSetting::setValue(AssessmentSetting::BASE_SCORE, ['value' => (float) $data['base_score']]);
        });

        return back()->with('success', 'Pengaturan nilai tersimpan. Berlaku untuk perhitungan berikutnya; rekap yang sudah final/dikunci tidak berubah.');
    }
}
