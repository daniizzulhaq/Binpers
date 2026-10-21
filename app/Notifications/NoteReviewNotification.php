<?php

namespace App\Notifications;

use App\Models\PerformanceNote;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Notifikasi persetujuan penilaian harian: dikirim (ke atasan), disetujui/ditolak (ke penilai). */
class NoteReviewNotification extends Notification
{
    public function __construct(
        private PerformanceNote $note,
        private string $action,
        private ?string $notes = null,
    ) {
    }

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $this->note->loadMissing(['subject.user', 'authorUser']);

        $subject = $this->note->subject?->user?->name ?? 'anggota';
        $author = $this->note->authorUser?->name ?? 'Penilai';
        $kind = strtolower($this->note->type->shortLabel());
        $date = $this->note->incident_date->format('d/m/Y');

        [$title, $message, $url] = match ($this->action) {
            'submitted' => [
                'Penilaian harian menunggu persetujuan',
                "{$author} memberi {$kind} kepada {$subject} ({$date}).",
                route('note-reviews.index'),
            ],
            'approved' => [
                'Penilaian harian disetujui',
                "Penilaian {$kind} untuk {$subject} ({$date}) disetujui.",
                route('notes.index'),
            ],
            default => [
                'Penilaian harian ditolak',
                "Penilaian {$kind} untuk {$subject} ({$date}) ditolak. Silakan ubah atau hapus.",
                route('notes.index', ['status' => 'rejected']),
            ],
        };

        return [
            'note_id' => $this->note->id,
            'action' => $this->action,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'notes' => $this->action === 'rejected' && $this->notes ? Str::limit($this->notes, 200) : null,
        ];
    }
}
