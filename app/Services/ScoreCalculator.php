<?php

namespace App\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class ScoreCalculator
{
    /**
     * Nilai akhir 0–100: skor tiap kriteria dinormalisasi sesuai skalanya,
     * lalu dirata-ratakan dengan bobot.
     *
     * @param  Collection<int, \App\Models\AssessmentCriterion>  $criteria
     * @param  array<int, float|int|string>  $scores  dikunci oleh id kriteria
     */
    public function calculate(Collection $criteria, array $scores): float
    {
        $totalWeight = (float) $criteria->sum('weight');

        if ($totalWeight <= 0) {
            throw new InvalidArgumentException('Total bobot kriteria harus lebih dari 0.');
        }

        $sum = 0.0;
        foreach ($criteria as $criterion) {
            if (! array_key_exists($criterion->id, $scores)) {
                throw new InvalidArgumentException("Skor untuk kriteria {$criterion->name} belum diisi.");
            }
            $sum += $criterion->normalize((float) $scores[$criterion->id]) * (float) $criterion->weight;
        }

        return round($sum / $totalWeight, 2);
    }

    public function gradeFor(float $score): string
    {
        $grades = collect(config('assessment.grades'))->sortByDesc('min')->values();

        foreach ($grades as $grade) {
            if ($score >= $grade['min']) {
                return $grade['label'];
            }
        }

        return $grades->last()['label'];
    }
}
