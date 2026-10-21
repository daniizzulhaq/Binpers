<?php

namespace App\Policies;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\User;
use App\Services\ReviewRoutingService;

class AssessmentPolicy
{
    public function __construct(private ReviewRoutingService $routing)
    {
    }

    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->can('reports.view')) {
            return true;
        }

        $personnelId = $user->personnel?->id;
        if ($personnelId === null) {
            return false;
        }

        if ($personnelId === $assessment->assessor_id) {
            return true;
        }

        // Penyetuju struktural: penilaian yang menjadi tanggung jawabnya atau pernah ia proses.
        if ($this->isStructuralReviewer($user, $assessment)) {
            return true;
        }

        // Anggota hanya boleh melihat hasil pribadinya yang sudah final.
        return $personnelId === $assessment->assessee_id
            && $assessment->status === AssessmentStatus::Finalized
            && $user->can('assessments.view-own');
    }

    /** Bukti pendukung: penilai pemilik, penyetuju struktural, dan pemegang izin laporan. Anggota tidak. */
    public function viewEvidence(User $user, Assessment $assessment): bool
    {
        return $user->can('reports.view')
            || $this->isOwn($user, $assessment)
            || $this->isStructuralReviewer($user, $assessment);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->can('assessments.create')
            && $user->personnel?->id === $assessment->assessor_id
            && $assessment->status->editable()
            && $assessment->period->isOpen();
    }

    public function submit(User $user, Assessment $assessment): bool
    {
        return $this->update($user, $assessment);
    }

    /** Hanya penanggung jawab persetujuan (atasan struktural atau pimpinan) yang boleh menyetujui. */
    public function approve(User $user, Assessment $assessment): bool
    {
        return $assessment->status === AssessmentStatus::Submitted
            && ! $this->isOwn($user, $assessment)
            && ! $this->isAssessee($user, $assessment)
            && $this->routing->canReview($user, $assessment);
    }

    /** Penanggung jawab persetujuan boleh mengembalikan penilaian yang dikirim (atau data lama yang Disetujui). */
    public function returnForRevision(User $user, Assessment $assessment): bool
    {
        return in_array($assessment->status, [AssessmentStatus::Submitted, AssessmentStatus::Approved], true)
            && ! $this->isOwn($user, $assessment)
            && ! $this->isAssessee($user, $assessment)
            && $this->routing->canReview($user, $assessment);
    }

    /**
     * Penilaian baru langsung final saat disetujui. Ini hanya untuk data lama yang
     * sudah Disetujui: difinalisasi oleh penyetujunya, bukan HRD.
     */
    public function finalize(User $user, Assessment $assessment): bool
    {
        return $assessment->status === AssessmentStatus::Approved
            && ! $this->isOwn($user, $assessment)
            && ! $this->isAssessee($user, $assessment)
            && $this->routing->canReview($user, $assessment);
    }

    // ---------------------------------------------------------------

    private function isOwn(User $user, Assessment $assessment): bool
    {
        return $user->personnel?->id !== null && $user->personnel->id === $assessment->assessor_id;
    }

    /** Anggota yang dinilai tidak pernah boleh menyetujui penilaian atas dirinya. */
    private function isAssessee(User $user, Assessment $assessment): bool
    {
        return $user->personnel?->id !== null && $user->personnel->id === $assessment->assessee_id;
    }

    private function isStructuralReviewer(User $user, Assessment $assessment): bool
    {
        if (! $assessment->isStructuralReview() || ! $user->can('assessments.review-structural')) {
            return false;
        }

        return $this->routing->canReview($user, $assessment)
            || $assessment->approvals()->where('user_id', $user->id)->exists();
    }
}
