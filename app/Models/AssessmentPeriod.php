<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentPeriod extends Model
{
    use Auditable;

    protected $fillable = ['name', 'start_date', 'end_date', 'status', 'description'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => PeriodStatus::class,
            'recap_locked_at' => 'datetime',
        ];
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(AssessmentCriterion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function monthlyScores(): HasMany
    {
        return $this->hasMany(MonthlyScore::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recap_locked_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', PeriodStatus::Open->value);
    }

    public function isOpen(): bool
    {
        return $this->status === PeriodStatus::Open;
    }

    public function totalWeight(): float
    {
        return (float) $this->criteria()->sum('weight');
    }

    /** Kriteria tidak boleh diubah jika periode ditutup atau sudah ada penilaian. */
    public function isCriteriaLocked(): bool
    {
        return $this->status === PeriodStatus::Closed || $this->assessments()->exists();
    }

    public function isRecapLocked(): bool
    {
        return $this->recap_locked_at !== null;
    }

    /** Periode yang mencakup tanggal ini. */
    public static function forDate($date): ?self
    {
        return static::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('start_date')
            ->first();
    }

    /** Apakah rekap bulan untuk tanggal ini sudah dikunci? */
    public static function recapLockedForDate($date): bool
    {
        return static::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereNotNull('recap_locked_at')
            ->exists();
    }
}
