<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\AssessmentPeriod;
use App\Models\PerformanceNote;
use App\Models\User;
use App\Services\DailyScoreService;

class PerformanceNotePolicy
{
    /** Melihat catatan/fotonya: pemegang laporan, pembuat, atau anggota yang dicatat. */
    public function view(User $user, PerformanceNote $note): bool
    {
        if ($user->can('reports.view')) {
            return true;
        }

        if ((int) $note->author_user_id === (int) $user->id) {
            return true;
        }

        return $user->personnel?->id !== null
            && (int) $user->personnel->id === (int) $note->subject_id;
    }

    public function update(User $user, PerformanceNote $note): bool
    {
        return $this->owns($user, $note) && $this->withinWindow($note) && $this->notLocked($note)
            && $this->recapOpen($note);
    }

    public function delete(User $user, PerformanceNote $note): bool
    {
        return $this->owns($user, $note) && $this->withinWindow($note) && $this->notLocked($note)
            && $this->recapOpen($note);
    }

    /** Hanya akun pembuat. HRD untuk semua jenis; penilai hanya jenis yang bukan khusus HRD. */
    private function owns(User $user, PerformanceNote $note): bool
    {
        if ((int) $note->author_user_id !== (int) $user->id) {
            return false;
        }

        return $user->hasRole(RoleName::Hrd->value)
            || (! $note->type->hrdOnly() && $user->can('assessments.create'));
    }

    /** Batas jam ubah/hapus (default 24 jam). */
    private function withinWindow(PerformanceNote $note): bool
    {
        $hours = (int) config('assessment.note_edit_hours', 24);

        return $note->created_at->gt(now()->subHours($hours));
    }

    /** Catatan pada bulan yang rekapnya sudah dikunci tidak boleh diubah. */
    private function notLocked(PerformanceNote $note): bool
    {
        return ! AssessmentPeriod::recapLockedForDate($note->incident_date);
    }

    /** Penilaian harian terkunci setelah rekap penilainya atas anggota itu dikirim. */
    private function recapOpen(PerformanceNote $note): bool
    {
        return ! app(DailyScoreService::class)->recapSubmitted($note->author_id, $note->subject_id, $note->incident_date);
    }
}
