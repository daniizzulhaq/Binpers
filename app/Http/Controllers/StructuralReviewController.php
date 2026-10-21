<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalAction;
use App\Http\Requests\StructuralReviewRequest;
use App\Models\Assessment;
use App\Services\AssessmentService;
use App\Services\ReviewRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Persetujuan penilaian oleh atasan struktural (Kabag, Kasat, Kasi, Kasi Pengawas, Kapolsek). */
class StructuralReviewController extends Controller
{
    public function __construct(
        private AssessmentService $service,
        private ReviewRoutingService $routing,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->input('tab') === 'diproses' ? 'diproses' : 'menunggu';

        $with = ['period:id,name', 'assessor.user', 'assessor.currentAssignment.position', 'assessee.user', 'assesseeUnit'];

        $query = $tab === 'menunggu'
            ? $this->routing->queueFor($user)->latest('submitted_at')
            : Assessment::where('review_level', Assessment::REVIEW_STRUCTURAL)
                ->whereHas('approvals', fn ($q) => $q
                    ->where('user_id', $user->id)
                    ->whereIn('action', [ApprovalAction::Approve->value, ApprovalAction::Return->value]))
                ->latest('updated_at');

        return view('assessor.reviews.index', [
            'tab' => $tab,
            'assessments' => $query->with($with)->paginate(10)->withQueryString(),
            'pendingCount' => $tab === 'menunggu' ? null : $this->routing->queueFor($user)->count(),
        ]);
    }

    public function show(Request $request, Assessment $assessment): View
    {
        Gate::authorize('view', $assessment);
        abort_unless($assessment->isStructuralReview(), 404);

        $assessment->load([
            'period', 'reviewerUnit',
            'assessor.user', 'assessor.currentAssignment.unit', 'assessor.currentAssignment.position',
            'assessee.user', 'assesseeUnit', 'assesseePosition',
            'details.criterion', 'approvals.user',
        ]);

        return view('assessor.reviews.show', [
            'assessment' => $assessment,
            'canApprove' => Gate::allows('approve', $assessment),
            'canReturn' => Gate::allows('returnForRevision', $assessment),
            'canFinalize' => Gate::allows('finalize', $assessment),
        ]);
    }

    /** Data lama berstatus Disetujui: dikunci menjadi final oleh penyetujunya. */
    public function finalize(StructuralReviewRequest $request, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('finalize', $assessment);

        $this->service->finalize($assessment, $request->user(), $request->validated('notes'));

        return redirect()->route('assessor.reviews.index')->with('success', 'Penilaian difinalisasi.');
    }

    public function approve(StructuralReviewRequest $request, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('approve', $assessment);

        $this->service->approve($assessment, $request->user(), $request->validated('notes'));

        return redirect()->route('assessor.reviews.index')->with('success', 'Penilaian disetujui dan langsung final.');
    }

    public function returnForRevision(StructuralReviewRequest $request, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('returnForRevision', $assessment);

        $this->service->returnForRevision($assessment, $request->user(), $request->validated('notes'));

        return redirect()->route('assessor.reviews.index')->with('success', 'Penilaian dikembalikan kepada penilai untuk revisi.');
    }
}
