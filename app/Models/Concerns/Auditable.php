<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => AuditLog::record('created', $m, [], $m->getAttributes()));

        static::updated(function (Model $m) {
            $new = $m->getChanges();
            $old = array_intersect_key($m->getOriginal(), $new);
            AuditLog::record('updated', $m, $old, $new);
        });

        static::deleted(fn (Model $m) => AuditLog::record('deleted', $m, $m->getOriginal(), []));
    }

    /** Kolom yang tidak boleh masuk audit log. */
    public function auditExcluded(): array
    {
        return ['password', 'remember_token', 'updated_at'];
    }
}
