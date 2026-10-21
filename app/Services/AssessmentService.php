<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentApproval;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\Personnel;
use App\Models\User;
use App\Notifications\AssessmentStatusNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AssessmentService
{
    public function __construct(
        private ScoreCalculator $calculator,
        private PointService $points,
        private AssessorScopeService $scope,
        private ReviewRoutingService $routing,
        private DailyScoreService $daily,
    ) {
    }

    /** Apakah penilai berhak menilai anggota ini (aturan role + kewenangan manual HRD)? */
    public function isAuthorized(Personnel $assessor, Personnel $assessee): bool
    {
        return $this->scope->canAssess($assessor, $assessee);
    }

    /**
     * Simpan (atau perbarui) draft penilaian.
     *
     * Hanya kriteria milik periode ini yang diproses. Kunci lain pada $data['scores'] diabaikan.
     *
     * @param  array{
     *     scores?: array<int, mixed>,
     *     notes?: array<int, ?string>,
     *     evidence?: array<int, UploadedFile>,
     *     remove_evidence?: array<int, mixed>,
     *     general_notes?: ?string,
     *     member_notes?: ?string
     * }  $data
     */
    public function saveDraft(Personnel $assessor, AssessmentPeriod $period, Personnel $assessee, array $data): Assessment
    {
        if (! $period->isOpen()) {
            throw ValidationException::withMessages(['period' => 'Periode penilaian tidak sedang dibuka.']);
        }

        if (! $this->isAuthorized($assessor, $assessee)) {
            throw new AuthorizationException('Anda tidak berwenang menilai anggota ini.');
        }

        $stored = [];   // berkas baru: dibersihkan bila transaksi gagal
        $obsolete = []; // berkas lama: dihapus hanya setelah transaksi berhasil

        try {
            $assessment = DB::transaction(function () use ($assessor, $period, $assessee, $data, &$stored, &$obsolete) {
                $assessment = Assessment::where([
                    'assessment_period_id' => $period->id,
                    'assessor_id' => $assessor->id,
                    'assessee_id' => $assessee->id,
                ])->lockForUpdate()->first();

                if ($assessment && ! $assessment->status->editable()) {
                    throw ValidationException::withMessages([
                        'status' => "Penilaian berstatus {$assessment->status->label()} tidak dapat diubah.",
                    ]);
                }

                $assignment = $assessee->currentAssignment;

                $assessment ??= new Assessment([
                    'assessment_period_id' => $period->id,
                    'assessor_id' => $assessor->id,
                    'assessee_id' => $assessee->id,
                ]);

                $assessment->fill([
                    'assessee_unit_id' => $assignment?->organizational_unit_id,
                    'assessee_position_id' => $assignment?->position_id,
                    'general_notes' => $data['general_notes'] ?? null,
                    'member_notes' => $data['member_notes'] ?? null,
                ])->save();

                $existing = AssessmentDetail::where('assessment_id', $assessment->id)
                    ->get()->keyBy('assessment_criterion_id');

                $scores = $data['scores'] ?? [];

                foreach ($period->criteria as $criterion) {
                    $id = $criterion->id;

                    if (! array_key_exists($id, $scores)) {
                        continue;
                    }

                    $score = $scores[$id];
                    $detail = $existing->get($id);

                    // Skor dikosongkan: hapus baris beserta buktinya.
                    if ($score === null || $score === '') {
                        if ($detail) {
                            if ($detail->evidence_path) {
                                $obsolete[] = $detail->evidence_path;
                            }
                            $detail->delete();
                        }

                        continue;
                    }

                    if (! is_numeric($score)
                        || (float) $score < (float) $criterion->min_score
                        || (float) $score > (float) $criterion->max_score) {
                        throw ValidationException::withMessages([
                            "scores.{$id}" => "Skor {$criterion->name} harus antara {$criterion->min_score} dan {$criterion->max_score}.",
                        ]);
                    }

                    $detail ??= new AssessmentDetail([
                        'assessment_id' => $assessment->id,
                        'assessment_criterion_id' => $id,
                    ]);
                    $detail->score = $score;

                    if (array_key_exists($id, $data['notes'] ?? [])) {
                        $detail->note = $data['notes'][$id];
                    }

                    $file = $data['evidence'][$id] ?? null;

                    if ($file instanceof UploadedFile) {
                        if ($detail->evidence_path) {
                            $obsolete[] = $detail->evidence_path;
                        }
                        $detail->evidence_path = $file->store("evidence/{$assessment->id}", 'local');
                        $stored[] = $detail->evidence_path;
                    } elseif (! empty($data['remove_evidence'][$id]) && $detail->evidence_path) {
                        $obsolete[] = $detail->evidence_path;
                        $detail->evidence_path = null;
                    }

                    $detail->save();
                }

                return $assessment;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($stored);

            throw $e;
        }

        Storage::disk('local')->delete($obsolete);

        return $assessment;
    }

    /** Kirim rekap untuk diperiksa. Skor tiap kriteria dihitung otomatis dari penilaian harian. */
    public function submit(Assessment $assessment, User $actor): Assessment
    {
        $a = DB::transaction(function () use ($assessment, $actor) {
            $a = $this->lock($assessment);
            $this->ensureStatus($a, [AssessmentStatus::Draft, AssessmentStatus::Revision]);

            if (! $a->period->isOpen()) {
                throw ValidationException::withMessages(['period' => 'Periode penilaian sudah tidak dibuka.']);
            }

            $this->syncFromNotes($a);
            [$score, $grade] = $this->recalculate($a);

            // Tujuan persetujuan disimpan sekarang (dan dihitung ulang tiap kirim ulang),
            // agar tidak berubah bila penilai atau atasannya dimutasi.
            $route = $this->routing->resolve($a);

            $a = $this->transition($a, $actor, ApprovalAction::Submit, AssessmentStatus::Submitted, null, [
                'final_score' => $score,
                'grade' => $grade,
                'submitted_at' => now(),
                'review_level' => $route['review_level'],
                'reviewer_unit_id' => $route['reviewer_unit_id'],
            ]);

            // Penilaian Kapolres: tidak ada atasan di sistem, langsung disetujui dan final.
            if ($route['review_level'] === Assessment::REVIEW_SELF) {
                $a = $this->transition($a, $actor, ApprovalAction::Approve, AssessmentStatus::Approved, null, [
                    'approved_at' => now(),
                ]);
                $a = $this->lockFinal($a, $actor, null);
            }

            return $a;
        });

        $this->notify(
            $a->status === AssessmentStatus::Finalized ? ApprovalAction::Finalize : ApprovalAction::Submit,
            $a,
            $actor
        );

        return $a;
    }

    /** Kembalikan ke penilai untuk direvisi. Catatan wajib. */
    public function returnForRevision(Assessment $assessment, User $actor, string $notes): Assessment
    {
        $a = DB::transaction(function () use ($assessment, $actor, $notes) {
            $a = $this->lock($assessment);
            $this->ensureStatus($a, [AssessmentStatus::Submitted, AssessmentStatus::Approved]);
            $this->ensureNotOwn($a, $actor);
            $this->ensureNotAssessee($a, $actor);

            return $this->transition($a, $actor, ApprovalAction::Return, AssessmentStatus::Revision, $notes, [
                'revision_count' => $a->revision_count + 1,
                'approved_at' => null,
            ]);
        });

        $this->notify(ApprovalAction::Return, $a, $actor, $notes);

        return $a;
    }

    /**
     * Setujui sekaligus finalisasi: penyetuju (atasan struktural atau pimpinan) langsung mengunci nilai.
     * Riwayat tetap mencatat dua langkah: Disetujui lalu Difinalisasi.
     */
    public function approve(Assessment $assessment, User $actor, ?string $notes = null): Assessment
    {
        $a = DB::transaction(function () use ($assessment, $actor, $notes) {
            $a = $this->lock($assessment);
            $this->ensureStatus($a, [AssessmentStatus::Submitted]);
            $this->ensureNotOwn($a, $actor);
            $this->ensureNotAssessee($a, $actor);

            $a = $this->transition($a, $actor, ApprovalAction::Approve, AssessmentStatus::Approved, $notes, [
                'approved_at' => now(),
            ]);

            return $this->lockFinal($a, $actor, null);
        });

        $this->notify(ApprovalAction::Finalize, $a, $actor);

        return $a;
    }

    /** Finalisasi penilaian lama yang sudah berstatus Disetujui (sebelum alur setujui = final). */
    public function finalize(Assessment $assessment, User $actor, ?string $notes = null): Assessment
    {
        $a = DB::transaction(function () use ($assessment, $actor, $notes) {
            $a = $this->lock($assessment);
            $this->ensureStatus($a, [AssessmentStatus::Approved]);
            $this->ensureNotOwn($a, $actor);
            $this->ensureNotAssessee($a, $actor);

            return $this->lockFinal($a, $actor, $notes);
        });

        $this->notify(ApprovalAction::Finalize, $a, $actor);

        return $a;
    }

    // ---------------------------------------------------------------

    private function lock(Assessment $assessment): Assessment
    {
        return Assessment::with('period.criteria')->lockForUpdate()->findOrFail($assessment->id);
    }

    /** @param  AssessmentStatus[]  $allowed */
    private function ensureStatus(Assessment $a, array $allowed): void
    {
        if (! in_array($a->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Aksi tidak dapat dilakukan pada penilaian berstatus {$a->status->label()}.",
            ]);
        }
    }

    private function ensureNotOwn(Assessment $a, User $actor): void
    {
        if ($actor->personnel?->id === $a->assessor_id) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat memeriksa penilaian yang Anda buat sendiri.',
            ]);
        }
    }

    /** Anggota yang dinilai tidak boleh menyetujui/mengembalikan penilaian atas dirinya. */
    private function ensureNotAssessee(Assessment $a, User $actor): void
    {
        if ($actor->personnel?->id === $a->assessee_id) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat memeriksa penilaian atas diri Anda sendiri.',
            ]);
        }
    }

    /**
     * Isi skor tiap kriteria dari penilaian harian penilai atas anggota ini pada periode tersebut.
     * Dipanggil di dalam transaksi; setelah dikirim, penilaian harian terkait dikunci.
     */
    private function syncFromNotes(Assessment $a): void
    {
        $criteria = $a->period->criteria;
        $summary = $this->daily->summarize($a->period, $a->assessor_id, collect([$a->assessee_id]))->get($a->assessee_id);

        AssessmentDetail::where('assessment_id', $a->id)
            ->whereNotIn('assessment_criterion_id', $criteria->pluck('id'))
            ->delete();

        foreach ($criteria as $c) {
            $bucket = $summary['criteria'][$c->id];

            AssessmentDetail::updateOrCreate(
                ['assessment_id' => $a->id, 'assessment_criterion_id' => $c->id],
                ['score' => $this->daily->toCriterionScale($c, $bucket['score']), 'note' => $this->daily->describe($bucket)]
            );
        }
    }

    /** @return array{0: float, 1: string} */
    private function recalculate(Assessment $a): array
    {
        $criteria = $a->period->criteria;
        $scores = $a->details()->pluck('score', 'assessment_criterion_id')->all();

        $missing = $criteria->reject(fn ($c) => array_key_exists($c->id, $scores));
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'scores' => 'Kriteria belum diisi: '.$missing->pluck('name')->implode(', ').'.',
            ]);
        }

        // Poin penilaian harian "Umum" (tanpa kriteria) langsung menambah/mengurangi nilai akhir.
        $general = $this->daily->summarize($a->period, $a->assessor_id, collect([$a->assessee_id]))
            ->get($a->assessee_id)['general']['points'];

        $score = $this->daily->clamp($this->calculator->calculate($criteria, $scores) + $general);

        return [$score, $this->calculator->gradeFor($score)];
    }

    /** Hitung ulang nilai dari detail lalu kunci sebagai Final. Dipanggil di dalam transaksi. */
    private function lockFinal(Assessment $a, User $actor, ?string $notes): Assessment
    {
        [$score, $grade] = $this->recalculate($a);

        return $this->transition($a, $actor, ApprovalAction::Finalize, AssessmentStatus::Finalized, $notes, [
            'final_score' => $score,
            'grade' => $grade,
            'finalized_at' => now(),
            'finalized_by' => $actor->id,
        ]);
    }

    private function transition(
        Assessment $a,
        User $actor,
        ApprovalAction $action,
        AssessmentStatus $to,
        ?string $notes,
        array $extra = []
    ): Assessment {
        $from = $a->status;

        $a->forceFill(['status' => $to] + $extra)->save();

        AssessmentApproval::create([
            'assessment_id' => $a->id,
            'user_id' => $actor->id,
            'action' => $action,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'notes' => $notes,
        ]);

        return $a;
    }

    /** Kirim notifikasi setelah transaksi berhasil. Kegagalan dilaporkan, tidak membatalkan alur. */
    private function notify(ApprovalAction $action, Assessment $a, User $actor, ?string $notes = null): void
    {
        rescue(function () use ($action, $a, $actor, $notes) {
            $a->loadMissing(['period', 'assessor.user', 'assessee.user']);

            $recipients = match ($action) {
                ApprovalAction::Submit => $this->routing->approversFor($a),
                ApprovalAction::Return => collect([$a->assessor->user]),
                ApprovalAction::Approve => collect([$a->assessor->user]),
                ApprovalAction::Finalize => collect([$a->assessee->user, $a->assessor->user]),
            };

            $recipients = $recipients->filter()->unique('id')->reject(fn ($u) => $u->id === $actor->id);

            Notification::send($recipients, new AssessmentStatusNotification($a, $action, $notes));
        }, null, true);
    }
}
