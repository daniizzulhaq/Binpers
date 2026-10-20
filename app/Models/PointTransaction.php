<?php

namespace App\Models;

use App\Enums\PointType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    use Auditable;

    protected $fillable = [
        'personnel_id', 'assessment_id', 'reward_id',
        'type', 'points', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return ['type' => PointType::class, 'points' => 'integer'];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}
