<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use App\Models\Concerns\Auditable;
use App\Services\DailyScoreService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use Auditable;

    /** Tujuan persetujuan: atasan struktural penilai, atau pimpinan (Kapolres/Wakapolres/HRD). */
    public const REVIEW_STRUCTURAL = 'structural';
    public const REVIEW_LEADERSHIP = 'leadership';
    /** Penilaian oleh Wakapolres: hanya Kapolres yang menyetujui. */
    public const REVIEW_KAPOLRES = 'kapolres';
    /** Penilaian oleh Kapolres: langsung final saat dikirim (tidak ada atasan di sistem). */
    public const REVIEW_SELF = 'self';

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

    /** Unit induk yang kepalanya menyetujui (hanya untuk persetujuan struktural). */
    public function reviewerUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'reviewer_unit_id');
    }

    /** Data lama tanpa review_level diperlakukan sebagai persetujuan pimpinan. */
    public function isStructuralReview(): bool
    {
        return $this->review_level === self::REVIEW_STRUCTURAL && $this->reviewer_unit_id !== null;
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

    /** Penilaian harian penilai atas anggota ini pada periode rekap (dasar perhitungan nilai). */
    public function dailyNotes(): Collection
    {
        return app(DailyScoreService::class)
            ->notesQuery($this->period, $this->assessor_id, $this->assessee_id)
            ->with('criterion')
            ->latest('incident_date')->latest('id')
            ->get();
    }
}
