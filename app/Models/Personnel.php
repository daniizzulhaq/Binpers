<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Personnel extends Model
{
    use Auditable;

    protected $table = 'personnel';

    protected $fillable = [
        'user_id', 'nrp', 'rank', 'gender', 'place_of_birth',
        'birth_date', 'join_date', 'phone', 'address',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'join_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Riwayat penempatan, terbaru dulu. */
    public function assignments(): HasMany
    {
        return $this->hasMany(PersonnelAssignment::class)->orderByDesc('start_date');
    }

    /** Penempatan aktif saat ini (end_date kosong). */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(PersonnelAssignment::class)->whereNull('end_date')->latest('start_date');
    }

    public function assessmentsAsAssessee(): HasMany
    {
        return $this->hasMany(Assessment::class, 'assessee_id');
    }

    public function assessmentsAsAssessor(): HasMany
    {
        return $this->hasMany(Assessment::class, 'assessor_id');
    }

    public function authorizationsAsAssessor(): HasMany
    {
        return $this->hasMany(AssessmentAuthorization::class, 'assessor_id');
    }

    public function authorizationsAsAssessee(): HasMany
    {
        return $this->hasMany(AssessmentAuthorization::class, 'assessee_id');
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function totalPoints(): int
    {
        return (int) $this->pointTransactions()->sum('points');
    }
}
