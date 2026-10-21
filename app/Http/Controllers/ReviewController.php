<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Http\Requests\ReviewActionRequest;
use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Services\AssessmentService;
use App\Services\ReviewRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        private AssessmentService $service,
        private ReviewRoutingService $routing,
    ) {
    }

    /**
     * Tab "Antrean": penilaian yang dapat diproses pimpinan/HRD
     * (penilaian struktural yang masih menunggu atasannya disembunyikan).
     * Tab "Semua": seluruh penilaian, termasuk yang menunggu atasan struktural (hanya dipantau).
     */
    public function index(Request $request): View
    {
        $status = AssessmentStatus::tryFrom((string) $request->input('status', 'submitted'));
        $scope = $request->input('scope') === 'semua' ? 'semua' : 'antrean';

        $assessments = Assessment::with(['period', 'assessor.user', 'assessee.user', 'assesseeUnit', 'reviewerUnit'])
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->when($request->integer('period_id'), fn ($q, $id) => $q->where('assessment_period_id', $id))
            ->when($scope === 'antrean', fn ($q) => $q->where(fn ($w) => $w
                ->where('status', '!=', AssessmentStatus::Submitted->value)
                ->orWhere(fn ($l) => $this->routing->whereLeadership($l))))
            ->latest('submitted_at')->paginate(15)->withQueryString();

        return view('review.index', [
            'assessments' => $assessments,
            'periods' => AssessmentPeriod::orderByDesc('start_date')->get(),
            'statuses' => AssessmentStatus::cases(),
            'activeStatus' => $status,
            'scope' => $scope,
            'routing' => $this->routing,
        ]);
    }

    public function show(Assessment $assessment): View
    {
        Gate::authorize('view', $assessment);

        $assessment->load([
            'period', 'assessor.user', 'assessee.user', 'assesseeUnit', 'assesseePosition',
            'details.criterion', 'approvals.user', 'reviewerUnit',
        ]);

        return view('review.show', [
            'assessment' => $assessment,
            'routing' => $this->routing,
        ]);
    }

    public function approve(ReviewActionRequest $request, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('approve', $assessment);

        $this->service->approve($assessment, $request->user(), $request->validated('notes'));

        return redirect()->route('review.index')->with('success', 'Penilaian disetujui dan langsung final.');
    }

    public function returnForRevision(ReviewActionRequest $request, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('returnForRevision', $assessment);

        $this->service->returnForRevision($assessment, $request->user(), $request->validated('notes'));

        return redirect()->route('review.index')->with('success', 'Penilaian dikembalikan untuk revisi.');
    }

    public function finalize(ReviewActionRequest $request, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('finalize', $assessment);

        $this->service->finalize($assessment, $request->user(), $request->validated('notes'));

        return redirect()->route('review.index', ['status' => 'approved'])->with('success', 'Penilaian difinalisasi.');
    }
}
