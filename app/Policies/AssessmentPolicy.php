<?php

namespace App\Policies;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
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

        // Anggota hanya boleh melihat hasil pribadinya yang sudah final.
        return $personnelId === $assessment->assessee_id
            && $assessment->status === AssessmentStatus::Finalized
            && $user->can('assessments.view-own');
    }

    /** Bukti pendukung: hanya penilai pemilik dan pemegang izin laporan. Anggota tidak. */
    public function viewEvidence(User $user, Assessment $assessment): bool
    {
        return $user->can('reports.view')
            || ($user->personnel?->id !== null && $user->personnel->id === $assessment->assessor_id);
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

    public function approve(User $user, Assessment $assessment): bool
    {
        return $user->can('assessments.approve')
            && $assessment->status === AssessmentStatus::Submitted
            && $user->personnel?->id !== $assessment->assessor_id;
    }

    public function returnForRevision(User $user, Assessment $assessment): bool
    {
        return $user->can('assessments.approve')
            && in_array($assessment->status, [AssessmentStatus::Submitted, AssessmentStatus::Approved], true)
            && $user->personnel?->id !== $assessment->assessor_id;
    }

    public function finalize(User $user, Assessment $assessment): bool
    {
        return $user->can('assessments.finalize')
            && $assessment->status === AssessmentStatus::Approved;
    }
}
