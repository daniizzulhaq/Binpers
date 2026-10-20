<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentApproval extends Model
{
    protected $fillable = ['assessment_id', 'user_id', 'action', 'from_status', 'to_status', 'notes'];

    protected function casts(): array
    {
        return ['action' => ApprovalAction::class];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
