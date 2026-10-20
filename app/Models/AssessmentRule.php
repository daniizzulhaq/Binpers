<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AssessmentRule extends Model
{
    use Auditable;

    public const SCOPE_UNIT = 'unit';
    public const SCOPE_POLRES = 'polres';

    protected $fillable = [
        'assessor_role', 'scope', 'target_roles', 'excluded_position_ids',
        'is_mandatory', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'excluded_position_ids' => 'array',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
