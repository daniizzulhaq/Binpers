<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan penilaian yang diatur HRD. Nilai bawaan diambil dari config/assessment.php.
 */
class AssessmentSetting extends Model
{
    use Auditable;

    public const GRADES = 'grades';
    public const BASE_SCORE = 'base_score';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /** Cache per-request agar perhitungan massal tidak berulang kali query. */
    private static array $cache = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::$cache = []);
        static::deleted(fn () => static::$cache = []);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, static::$cache)) {
            $row = static::where('key', $key)->first();
            static::$cache[$key] = $row?->value;
        }

        return static::$cache[$key] ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Ambang predikat, urut dari nilai minimum tertinggi.
     *
     * @return array<int, array{min: float, label: string}>
     */
    public static function grades(): array
    {
        $defaults = collect(config('assessment.grades'));
        $saved = static::getValue(self::GRADES, []);

        // Label tetap mengikuti config; HRD hanya mengubah nilai minimum.
        return $defaults
            ->map(fn ($g) => [
                'label' => $g['label'],
                'min' => (float) ($saved[$g['label']] ?? $g['min']),
            ])
            ->sortByDesc('min')
            ->values()
            ->all();
    }

    public static function baseScore(): float
    {
        return (float) (static::getValue(self::BASE_SCORE)['value'] ?? config('assessment.base_score', 75));
    }
}
