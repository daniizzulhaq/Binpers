<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyScore extends Model
{
    protected $fillable = [
        'assessment_period_id', 'personnel_id', 'base_score', 'assessment_count',
        'bonus', 'penalty', 'total', 'grade',
    ];

    protected function casts(): array
    {
        return [
            'base_score' => 'decimal:2',
            'total' => 'decimal:2',
            'assessment_count' => 'integer',
            'bonus' => 'integer',
            'penalty' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AssessmentPeriod::class, 'assessment_period_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }
}
