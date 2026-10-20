<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
}
