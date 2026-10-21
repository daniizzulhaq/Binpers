<?php

namespace App\Services;

use App\Enums\NoteStatus;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\PerformanceNote;
use App\Models\User;
use App\Notifications\NoteReviewNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Persetujuan penilaian harian oleh atasan, dengan rute yang sama seperti rekap:
 * Kanit/Kasubag → Kabag/Kasat/Kasi, Kanit Polsek → Kapolsek, lainnya → pimpinan,
 * Wakapolres → Kapolres, Kapolres langsung disetujui.
 */
class NoteReviewService
{
    public function __construct(private ReviewRoutingService $routing)
    {
    }

    /** Catatan ini perlu persetujuan atasan? (Apresiasi/Teguran dari penilai, bukan HRD.) */
    public function needsReview(PerformanceNote $note): bool
    {
        return $note->author_id !== null
            && $note->type->hasPoints()
            && ! $note->type->hrdOnly()
            && ! $note->authorUser?->hasRole(RoleName::Hrd->value);
    }

    /** Kirim (atau kirim ulang setelah diubah) ke atasan; penilaian Kapolres langsung disetujui. */
    public function submit(PerformanceNote $note): void
    {
        if (! $this->needsReview($note)) {
            return;
        }

        $route = $this->routing->resolveForAssessor($note->author);
        $self = $route['review_level'] === Assessment::REVIEW_SELF;

        $note->forceFill($route + [
            'status' => $self ? NoteStatus::Approved : NoteStatus::Pending,
            'reviewed_by' => $self ? $note->author_user_id : null,
            'reviewed_at' => $self ? now() : null,
            'review_notes' => null,
        ])->save();

        if (! $self) {
            Notification::send(
                $this->routing->noteApproversFor($note),
                new NoteReviewNotification($note, 'submitted')
            );
        }
    }

    public function approve(PerformanceNote $note, User $actor, ?string $notes = null): void
    {
        $this->decide($note, $actor, NoteStatus::Approved, $notes);
    }

    public function reject(PerformanceNote $note, User $actor, string $reason): void
    {
        $this->decide($note, $actor, NoteStatus::Rejected, $reason);
    }

    private function decide(PerformanceNote $note, User $actor, NoteStatus $status, ?string $notes): void
    {
        DB::transaction(function () use ($note, $actor, $status, $notes) {
            $fresh = PerformanceNote::lockForUpdate()->findOrFail($note->id);

            if ($fresh->status !== NoteStatus::Pending) {
                throw ValidationException::withMessages(['note' => 'Penilaian harian ini sudah diproses.']);
            }

            $fresh->forceFill([
                'status' => $status,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ])->save();

            $note->setRawAttributes($fresh->getAttributes(), true);
        });

        if ($note->authorUser) {
            $note->authorUser->notify(new NoteReviewNotification($note, $status->value, $notes));
        }
    }
}
