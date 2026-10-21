<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Http\Requests\AssessmentScoreRequest;
use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Models\Personnel;
use App\Services\AssessmentService;
use App\Services\AssessorScopeService;
use App\Services\DailyScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function __construct(private AssessmentService $service, private DailyScoreService $daily)
    {
    }

    /** Daftar anggota yang menjadi kewenangan penilai, dengan nilai sementara dari penilaian harian bulan ini. */
    public function index(Request $request, AssessorScopeService $scope): View
    {
        $me = $this->personnel($request);
        $period = AssessmentPeriod::open()->first();

        $assessed = $period
            ? Assessment::where('assessment_period_id', $period->id)->where('assessor_id', $me->id)->get()->keyBy('assessee_id')
            : collect();

        $mandatory = $scope->mandatoryQuery($me)->pluck('id')->flip();
        $total = $scope->assesseeQuery($me)->count();

        $kind = $request->input('kind', $mandatory->isNotEmpty() ? 'wajib' : 'semua');
        $kind = in_array($kind, ['wajib', 'semua'], true) ? $kind : 'semua';
        $status = $request->string('status')->value();

        $people = $scope->assesseeQuery($me)
            ->with(['user.roles', 'currentAssignment.unit', 'currentAssignment.position'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w->where('nrp', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->when($kind === 'wajib', fn ($q) => $q->whereIn('personnel.id', $mandatory->keys()))
            ->when($status === 'belum', fn ($q) => $q->whereNotIn('personnel.id', $assessed->keys()))
            ->when($status && AssessmentStatus::tryFrom($status), fn ($q) => $q->whereIn(
                'personnel.id',
                $assessed->filter(fn ($a) => $a->status->value === $status)->keys()
            ))
            ->orderBy('nrp')
            ->paginate(15)->withQueryString();

        $summary = $period
            ? $this->daily->summarize($period, $me->id, $people->getCollection()->pluck('id'))
            : collect();

        return view('assessor.assessments.index', [
            'period' => $period,
            'people' => $people,
            'assessed' => $assessed,
            'summary' => $summary,
            'mandatory' => $mandatory,
            'kind' => $kind,
            'counts' => [
                'wajib' => $mandatory->count(),
                'semua' => $total,
            ],
            'statuses' => AssessmentStatus::cases(),
        ]);
    }

    public function history(Request $request): View
    {
        $me = $this->personnel($request);

        $assessments = Assessment::with(['period', 'assessee.user', 'assesseeUnit'])
            ->where('assessor_id', $me->id)
            ->when($request->integer('period_id'), fn ($q, $id) => $q->where('assessment_period_id', $id))
            ->when(AssessmentStatus::tryFrom((string) $request->input('status')), fn ($q, $s) => $q->where('status', $s->value))
            ->latest('updated_at')->paginate(12)->withQueryString();

        return view('assessor.assessments.history', [
            'assessments' => $assessments,
            'periods' => AssessmentPeriod::orderByDesc('start_date')->get(),
            'statuses' => AssessmentStatus::cases(),
        ]);
    }

    public function form(Request $request, AssessmentPeriod $period, Personnel $assessee): View|RedirectResponse
    {
        $me = $this->personnel($request);

        abort_unless($this->service->isAuthorized($me, $assessee), 403, 'Anda tidak berwenang menilai anggota ini.');

        if (! $period->isOpen()) {
            return redirect()->route('assessor.assessments.index')->with('error', 'Periode penilaian tidak sedang dibuka.');
        }

        $assessment = Assessment::with('details')->where([
            'assessment_period_id' => $period->id,
            'assessor_id' => $me->id,
            'assessee_id' => $assessee->id,
        ])->first();

        if ($assessment && ! $assessment->status->editable()) {
            return redirect()->route('assessor.assessments.show', $assessment)
                ->with('warning', 'Penilaian ini sudah dikirim dan tidak dapat diubah.');
        }

        return view('assessor.assessments.form', [
            'period' => $period,
            'assessee' => $assessee->load(['user', 'currentAssignment.unit', 'currentAssignment.position']),
            'assessment' => $assessment,
            'criteria' => $period->criteria,
            'summary' => $this->daily->summarize($period, $me->id, collect([$assessee->id]))->get($assessee->id),
            'notes' => $this->daily->notesQuery($period, $me->id, $assessee->id)
                ->with('criterion')->latest('incident_date')->latest('id')->get(),
            'baseScore' => $this->daily->baseScore(),
            'revisionNotes' => $assessment?->approvals()->where('action', 'return')->first()?->notes,
        ]);
    }

    /** Simpan catatan rekap sebagai draft, atau simpan lalu kirim bila tombol "Kirim Rekap" ditekan. */
    public function save(AssessmentScoreRequest $request, AssessmentPeriod $period, Personnel $assessee): RedirectResponse
    {
        $data = $request->validated();

        $assessment = $this->service->saveDraft($this->personnel($request), $period, $assessee, $data);

        if ($data['action'] === 'submit') {
            $assessment = $this->service->submit($assessment, $request->user());

            return redirect()->route('assessor.assessments.show', $assessment)->with('success', $this->submittedMessage($assessment));
        }

        return redirect()->route('assessor.assessments.form', [$period, $assessee])->with('success', 'Catatan rekap tersimpan.');
    }

    public function submit(Request $request, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('submit', $assessment);

        $assessment = $this->service->submit($assessment, $request->user());

        return redirect()->route('assessor.assessments.show', $assessment)->with('success', $this->submittedMessage($assessment));
    }

    public function show(Request $request, Assessment $assessment): View
    {
        // Halaman ini memuat catatan internal: hanya untuk penilai pembuatnya.
        abort_unless($assessment->assessor_id === $this->personnel($request)->id, 403);

        $assessment->load([
            'period', 'assessee.user', 'assesseeUnit', 'assesseePosition',
            'details.criterion', 'approvals.user',
        ]);

        return view('assessor.assessments.show', compact('assessment'));
    }

    private function submittedMessage(Assessment $assessment): string
    {
        return $assessment->status === AssessmentStatus::Finalized
            ? 'Rekap penilaian dikirim dan langsung final.'
            : 'Rekap penilaian berhasil dikirim untuk disetujui.';
    }

    private function personnel(Request $request): Personnel
    {
        $personnel = $request->user()->personnel;
        abort_if($personnel === null, 403, 'Data personel Anda belum dibuat. Hubungi HRD.');

        return $personnel;
    }
}
