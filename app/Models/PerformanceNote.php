<?php

namespace App\Models;

use App\Enums\NoteLevel;
use App\Enums\NoteType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceNote extends Model
{
    use Auditable;

    protected $fillable = [
        'author_id', 'author_user_id', 'subject_id', 'unit_id', 'assessment_criterion_id',
        'type', 'level', 'points', 'title', 'note', 'image_path', 'incident_date',
    ];

    protected function casts(): array
    {
        return [
            'type' => NoteType::class,
            'level' => NoteLevel::class,
            'points' => 'integer',
            'incident_date' => 'date',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'author_id');
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'subject_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'unit_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(AssessmentCriterion::class, 'assessment_criterion_id');
    }

    /** Nama kriteria; catatan tanpa kriteria dihitung sebagai "Umum". */
    public function criterionName(): string
    {
        return $this->criterion?->name ?? 'Umum';
    }

    /** Nama pencatat (akun), dengan cadangan ke data personel. */
    public function authorName(): string
    {
        return $this->authorUser?->name ?? $this->author?->user?->name ?? '—';
    }

    /** Jabatan pencatat; bila pencatat tidak punya data personel (mis. HRD) ditampilkan "HRD". */
    public function authorPosition(): ?string
    {
        return $this->author?->currentAssignment?->position?->name
            ?? ($this->author_id === null ? 'HRD' : null);
    }

    public function levelLabel(): ?string
    {
        return $this->level?->label($this->type);
    }
}
