<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentCriterion extends Model
{
    use Auditable;

    protected $table = 'assessment_criteria';

    protected $fillable = [
        'assessment_period_id', 'name', 'description',
        'weight', 'min_score', 'max_score', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'min_score' => 'decimal:2',
            'max_score' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AssessmentPeriod::class, 'assessment_period_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AssessmentDetail::class, 'assessment_criterion_id');
    }

    /** Ubah skor sesuai skala kriteria ini menjadi 0–100. */
    public function normalize(float $score): float
    {
        $min = (float) $this->min_score;
        $range = (float) $this->max_score - $min;

        return $range > 0 ? (($score - $min) / $range) * 100 : 0.0;
    }
}
