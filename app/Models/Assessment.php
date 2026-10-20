<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use Auditable;

    /**
     * Kolom alur kerja (status, nilai akhir, waktu) sengaja TIDAK fillable.
     * Hanya AssessmentService yang boleh mengubahnya lewat forceFill().
     */
    protected $fillable = [
        'assessment_period_id', 'assessor_id', 'assessee_id',
        'assessee_unit_id', 'assessee_position_id',
        'general_notes', 'member_notes',
    ];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return [
            'status' => AssessmentStatus::class,
            'final_score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AssessmentPeriod::class, 'assessment_period_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'assessor_id');
    }

    public function assessee(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'assessee_id');
    }

    public function assesseeUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'assessee_unit_id');
    }

    public function assesseePosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'assessee_position_id');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AssessmentDetail::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AssessmentApproval::class)->latest('id');
    }
}
