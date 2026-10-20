<?php

namespace App\Models;

use App\Enums\UnitType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalUnit extends Model
{
    use Auditable;

    protected $fillable = ['parent_id', 'code', 'name', 'type', 'is_active'];

    protected function casts(): array
    {
        return ['type' => UnitType::class, 'is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PersonnelAssignment::class, 'organizational_unit_id');
    }

    /** ID unit ini beserta seluruh turunannya. */
    public static function descendantIdsOf(int $id): array
    {
        $ids = [$id];
        $frontier = [$id];

        while ($frontier) {
            $frontier = array_values(array_diff(
                static::whereIn('parent_id', $frontier)->pluck('id')->all(),
                $ids
            ));
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }
}
