<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentDetail extends Model
{
    use Auditable;

    protected $fillable = ['assessment_id', 'assessment_criterion_id', 'score', 'note', 'evidence_path'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(AssessmentCriterion::class, 'assessment_criterion_id');
    }
}
