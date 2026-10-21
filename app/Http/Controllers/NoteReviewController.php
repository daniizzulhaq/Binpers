<?php

namespace App\Http\Controllers;

use App\Enums\NoteStatus;
use App\Models\PerformanceNote;
use App\Services\NoteReviewService;
use App\Services\ReviewRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Persetujuan penilaian harian oleh atasan (struktural atau pimpinan). */
class NoteReviewController extends Controller
{
    public function __construct(
        private NoteReviewService $reviews,
        private ReviewRoutingService $routing,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->input('tab') === 'diproses' ? 'diproses' : 'menunggu';

        $query = $tab === 'menunggu'
            ? $this->routing->noteQueueFor($user)->oldest('incident_date')->oldest('id')
            : PerformanceNote::where('reviewed_by', $user->id)
                ->where('author_user_id', '!=', $user->id)
                ->whereIn('status', [NoteStatus::Approved->value, NoteStatus::Rejected->value])
                ->latest('reviewed_at');

        return view('note-reviews.index', [
            'tab' => $tab,
            'notes' => $query->with([
                'authorUser', 'author.currentAssignment.position', 'subject.user', 'unit', 'criterion',
            ])->paginate(20)->withQueryString(),
            'pendingCount' => $this->routing->noteQueueFor($user)->count(),
        ]);
    }

    public function approve(Request $request, PerformanceNote $note): RedirectResponse
    {
        Gate::authorize('review', $note);

        $this->reviews->approve($note, $request->user());

        return back()->with('success', 'Penilaian harian disetujui.');
    }

    public function reject(Request $request, PerformanceNote $note): RedirectResponse
    {
        Gate::authorize('review', $note);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], [], ['reason' => 'alasan']);

        $this->reviews->reject($note, $request->user(), $data['reason']);

        return back()->with('success', 'Penilaian harian ditolak dan dikembalikan ke penilai.');
    }

    /** Setujui beberapa sekaligus (yang dicentang). */
    public function approveMany(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer']]);

        $done = 0;
        foreach (PerformanceNote::whereIn('id', $data['ids'])->get() as $note) {
            if ($request->user()->can('review', $note)) {
                $this->reviews->approve($note, $request->user());
                $done++;
            }
        }

        return back()->with('success', "{$done} penilaian harian disetujui.");
    }
}
