<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Enums\NoteStatus;
use App\Enums\RoleName;
use App\Enums\UnitType;
use App\Models\Assessment;
use App\Models\OrganizationalUnit;
use App\Models\PerformanceNote;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Menentukan siapa yang menyetujui sebuah penilaian.
 *
 * - Kanit/Kasubag di Polres → kepala bagian/satuan/seksi induk terdekat.
 * - Kanit/Kasium/Ka SPKT di Polsek → Kapolsek.
 * - Penilai lain, atau bila penyetuju struktural tidak ada → pimpinan (Kapolres/Wakapolres/HRD).
 */
class ReviewRoutingService
{
    /** Batas penelusuran parent_id agar data melingkar tidak membuat loop. */
    private const MAX_DEPTH = 10;

    /** @var array<int, int[]> cache unit yang dikepalai per pengguna selama satu request */
    private array $headedCache = [];

    /**
     * Tujuan persetujuan untuk penilaian ini, dihitung dari penempatan aktif penilai.
     *
     * @return array{review_level: string, reviewer_unit_id: ?int}
     */
    public function resolve(Assessment $assessment): array
    {
        return $this->resolveForAssessor($assessment->assessor()->with(['user', 'currentAssignment.unit'])->first());
    }

    /**
     * Tujuan persetujuan berdasarkan penempatan aktif penilai (dipakai rekap dan penilaian harian).
     *
     * @return array{review_level: string, reviewer_unit_id: ?int}
     */
    public function resolveForAssessor(?Personnel $assessor): array
    {
        $leadership = ['review_level' => Assessment::REVIEW_LEADERSHIP, 'reviewer_unit_id' => null];

        $assessor?->loadMissing(['user', 'currentAssignment.unit']);
        $user = $assessor?->user;

        // Kapolres pimpinan tertinggi di sistem: penilaiannya langsung final.
        if ($user?->hasRole(RoleName::Kapolres->value)) {
            return ['review_level' => Assessment::REVIEW_SELF, 'reviewer_unit_id' => null];
        }

        // Wakapolres: hanya Kapolres (bukan sesama Wakapolres atau yang dinilai).
        if ($user?->hasRole(RoleName::Wakapolres->value)) {
            return ['review_level' => Assessment::REVIEW_KAPOLRES, 'reviewer_unit_id' => null];
        }

        if (! $user || ! $user->hasAnyRole(RoleName::unitHeads())) {
            return $leadership;
        }

        $unit = $this->structuralParent($assessor->currentAssignment?->unit);

        if (! $unit) {
            return $leadership;
        }

        $approvers = $this->headsOf($unit->id, $this->approverRolesFor($unit->type))
            ->reject(fn (User $u) => $u->id === $user->id);

        return $approvers->isEmpty()
            ? $leadership
            : ['review_level' => Assessment::REVIEW_STRUCTURAL, 'reviewer_unit_id' => $unit->id];
    }

    /** Pengguna yang berhak menyetujui penilaian ini (untuk notifikasi). */
    public function approversFor(Assessment $assessment): Collection
    {
        $assessment->loadMissing(['assessor.user', 'assessee.user', 'reviewerUnit']);

        // Penilai dan anggota yang dinilai tidak pernah menjadi penyetuju.
        return $this->approversOf($assessment, [$assessment->assessor?->user_id, $assessment->assessee?->user_id]);
    }

    /** Pengguna yang berhak menyetujui penilaian harian ini (untuk notifikasi). */
    public function noteApproversFor(PerformanceNote $note): Collection
    {
        $note->loadMissing(['subject', 'reviewerUnit']);

        return $this->approversOf($note, [$note->author_user_id, $note->subject?->user_id]);
    }

    /**
     * Apakah pengguna ini penanggung jawab persetujuan rekap / penilaian harian tersebut.
     * Status dan larangan memeriksa milik sendiri / diri sendiri diperiksa di policy.
     */
    public function canReview(User $user, Assessment|PerformanceNote $item): bool
    {
        if (! $user->is_active || $item->review_level === Assessment::REVIEW_SELF) {
            return false;
        }

        if ($item->review_level === Assessment::REVIEW_KAPOLRES) {
            return $user->hasRole(RoleName::Kapolres->value);
        }

        if (! $item->isStructuralReview()) {
            return $user->can('assessments.approve');
        }

        return $user->can('assessments.review-structural')
            && in_array($item->reviewer_unit_id, $this->headedUnitIds($user), true);
    }

    /**
     * Penilaian yang menunggu tindakan pengguna ini: dikirim (perlu disetujui),
     * atau data lama berstatus Disetujui yang masih perlu difinalisasi.
     */
    public function queueFor(User $user): Builder
    {
        $query = Assessment::query()->whereIn('status', [AssessmentStatus::Submitted->value, AssessmentStatus::Approved->value]);

        return $this->scopeReviewable($query, $user, 'assessor_id', 'assessee_id');
    }

    /** Penilaian harian yang menunggu persetujuan pengguna ini. */
    public function noteQueueFor(User $user): Builder
    {
        $query = PerformanceNote::query()->where('status', NoteStatus::Pending->value);

        return $this->scopeReviewable($query, $user, 'author_id', 'subject_id');
    }

    /** Batasi ke item yang tujuan persetujuannya pengguna ini (bukan milik sendiri / tentang dirinya). */
    private function scopeReviewable(Builder $query, User $user, string $authorColumn, string $subjectColumn): Builder
    {
        if (! $user->is_active) {
            return $query->whereRaw('1 = 0');
        }

        $unitIds = $user->can('assessments.review-structural') ? $this->headedUnitIds($user) : [];
        $leader = $user->can('assessments.approve');
        $kapolres = $user->hasRole(RoleName::Kapolres->value);

        if ($user->personnel) {
            $query->where(fn (Builder $q) => $q->whereNull($authorColumn)->orWhere($authorColumn, '!=', $user->personnel->id))
                ->where($subjectColumn, '!=', $user->personnel->id);
        }

        return $query->where(function (Builder $w) use ($unitIds, $leader, $kapolres) {
            $w->whereRaw('1 = 0');

            if ($leader) {
                $w->orWhere(fn (Builder $q) => $q
                    ->where(fn (Builder $l) => $l->whereNull('review_level')->orWhere('review_level', Assessment::REVIEW_LEADERSHIP))
                    ->orWhere(fn (Builder $l) => $l->where('review_level', Assessment::REVIEW_STRUCTURAL)->whereNull('reviewer_unit_id')));
            }

            if ($kapolres) {
                $w->orWhere('review_level', Assessment::REVIEW_KAPOLRES);
            }

            if ($unitIds) {
                $w->orWhere(fn (Builder $q) => $q
                    ->where('review_level', Assessment::REVIEW_STRUCTURAL)
                    ->whereIn('reviewer_unit_id', $unitIds));
            }
        });
    }

    /**
     * Batasi query ke penilaian yang tidak ditangani atasan struktural
     * (pimpinan, khusus Kapolres, langsung final, dan data lama tanpa review_level).
     */
    public function whereLeadership(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('review_level')
            ->orWhere('review_level', '!=', Assessment::REVIEW_STRUCTURAL)
            ->orWhereNull('reviewer_unit_id'));
    }

    /** Label tujuan persetujuan untuk ditampilkan. */
    public function targetLabel(Assessment|PerformanceNote $assessment): string
    {
        return match (true) {
            $assessment->isStructuralReview() => $assessment->reviewerUnit?->name ?? 'Atasan struktural',
            $assessment->review_level === Assessment::REVIEW_SELF => 'Langsung final (penilaian Kapolres)',
            $assessment->review_level === Assessment::REVIEW_KAPOLRES => 'Kapolres',
            default => 'Pimpinan (Kapolres / Wakapolres)',
        };
    }

    // ---------------------------------------------------------------

    /** Calon penyetuju menurut tujuan persetujuan, tanpa pengguna yang dikecualikan. */
    private function approversOf(Assessment|PerformanceNote $item, array $excluded): Collection
    {
        $users = match (true) {
            $item->review_level === Assessment::REVIEW_SELF => collect(),
            $item->isStructuralReview() && $item->reviewerUnit !== null => $this->headsOf(
                $item->reviewer_unit_id,
                $this->approverRolesFor($item->reviewerUnit->type)
            ),
            $item->review_level === Assessment::REVIEW_KAPOLRES => User::role(RoleName::Kapolres->value)
                ->where('is_active', true)->get(),
            default => User::permission('assessments.approve')->where('is_active', true)->get(),
        };

        return $users->reject(fn (User $u) => in_array($u->id, $excluded, true))->values();
    }

    /** Unit induk terdekat bertipe bagian/satuan/seksi/polsek, dimulai dari unit itu sendiri. */
    private function structuralParent(?OrganizationalUnit $unit): ?OrganizationalUnit
    {
        $depth = 0;

        while ($unit && $depth++ < self::MAX_DEPTH) {
            if ($this->approverRolesFor($unit->type) !== null) {
                return $unit;
            }

            if ($unit->type === UnitType::Polres) {
                return null;
            }

            $unit = $unit->parent;
        }

        return null;
    }

    /** Role kepala unit yang menyetujui, atau null bila unit ini bukan unit induk struktural. */
    private function approverRolesFor(?UnitType $type): ?array
    {
        return match ($type) {
            UnitType::Bagian, UnitType::Satuan, UnitType::Seksi => [
                RoleName::Kabag->value, RoleName::Kasat->value,
                RoleName::Kasi->value, RoleName::KasiPengawas->value,
            ],
            UnitType::Polsek => [RoleName::Kapolsek->value],
            default => null,
        };
    }

    /** Akun aktif dengan role kepala yang penempatan aktifnya tepat di unit ini. */
    private function headsOf(int $unitId, ?array $roles): Collection
    {
        if (! $roles) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->role($roles)
            ->whereHas('personnel.currentAssignment', fn ($a) => $a->where('organizational_unit_id', $unitId))
            ->get();
    }

    /**
     * Unit yang dikepalai pengguna ini: unit penempatan aktifnya,
     * bila role-nya sesuai dengan tipe unit tersebut.
     *
     * @return int[]
     */
    private function headedUnitIds(User $user): array
    {
        return $this->headedCache[$user->id] ??= (function () use ($user) {
            $unit = $user->personnel?->currentAssignment?->unit;
            $roles = $unit ? $this->approverRolesFor($unit->type) : null;

            return $roles && $user->hasAnyRole($roles) ? [$unit->id] : [];
        })();
    }
}
