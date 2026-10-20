<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event', 'auditable_type', 'auditable_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Audit log bersifat append-only: tidak boleh diubah atau dihapus lewat Eloquent. */
    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $event, Model $subject, array $old = [], array $new = []): void
    {
        $excluded = method_exists($subject, 'auditExcluded') ? $subject->auditExcluded() : [];
        $old = Arr::except($old, $excluded);
        $new = Arr::except($new, $excluded);

        if ($event === 'updated' && $new === []) {
            return;
        }

        static::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 255, '') ?: null,
        ]);
    }
}
