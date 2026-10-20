<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAuthorization extends Model
{
    use Auditable;

    protected $fillable = [
        'assessor_id', 'assessee_id', 'valid_from', 'valid_until',
        'is_active', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'assessor_id');
    }

    public function assessee(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'assessee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Kewenangan yang aktif dan masih berlaku hari ini. */
    public function scopeEffective(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('valid_from')->orWhereDate('valid_from', '<=', today()))
            ->where(fn ($w) => $w->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()));
    }
}
