<?php

namespace App\Models;

use App\Enums\NoteLevel;
use App\Enums\NoteType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class NotePointSetting extends Model
{
    use Auditable;

    protected $fillable = ['type', 'level', 'points'];

    protected function casts(): array
    {
        return [
            'type' => NoteType::class,
            'level' => NoteLevel::class,
            'points' => 'integer',
        ];
    }

    /** Poin bertanda untuk jenis + tingkat: apresiasi positif, teguran negatif. */
    public static function signedPoints(NoteType $type, ?NoteLevel $level): int
    {
        if (! $type->hasPoints() || $level === null) {
            return 0;
        }

        $points = (int) static::where('type', $type->value)->where('level', $level->value)->value('points');

        return $points * $type->sign();
    }

    /** @return array<string, array<string, int>> mis. ['positive' => ['light' => 1, ...], ...] */
    public static function map(): array
    {
        return static::all()
            ->groupBy(fn (self $s) => $s->type->value)
            ->map(fn ($group) => $group->mapWithKeys(fn (self $s) => [$s->level->value => $s->points]))
            ->toArray();
    }
}
