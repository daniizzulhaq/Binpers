<?php

namespace App\Notifications;

use App\Enums\ApprovalAction;
use App\Models\Assessment;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AssessmentStatusNotification extends Notification
{
    public function __construct(
        private Assessment $assessment,
        private ApprovalAction $action,
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
        $period = $this->assessment->period->name;
        $assessee = $this->assessment->assessee->user->name;
        $isAssessee = $notifiable->personnel?->id === $this->assessment->assessee_id;

        [$title, $message] = match ($this->action) {
            ApprovalAction::Submit => [
                'Penilaian menunggu pemeriksaan',
                "Penilaian {$assessee} periode {$period} telah dikirim.",
            ],
            ApprovalAction::Return => [
                'Penilaian perlu direvisi',
                "Penilaian {$assessee} periode {$period} dikembalikan untuk revisi.",
            ],
            ApprovalAction::Approve => [
                'Penilaian disetujui',
                "Penilaian {$assessee} periode {$period} disetujui dan menunggu finalisasi.",
            ],
            ApprovalAction::Finalize => $isAssessee
                ? ['Hasil penilaian tersedia', "Hasil penilaian kinerja Anda periode {$period} telah final."]
                : ['Penilaian difinalisasi', "Penilaian {$assessee} periode {$period} telah final."],
        };

        return [
            'assessment_id' => $this->assessment->id,
            'action' => $this->action->value,
            'title' => $title,
            'message' => $message,
            // Catatan revisi hanya untuk penilai; tidak pernah dikirim ke anggota.
            'notes' => ($this->action === ApprovalAction::Return && $this->notes)
                ? Str::limit($this->notes, 200)
                : null,
        ];
    }
}
