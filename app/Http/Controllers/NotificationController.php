<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('notifications.index', [
            'notifications' => $user->notifications()->paginate(15),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /** Tandai dibaca lalu arahkan ke halaman yang sesuai dengan peran pengguna pada penilaian itu. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        $assessmentId = $notification->data['assessment_id'] ?? null;

        return redirect($assessmentId ? $this->targetUrl($user, (int) $assessmentId) : route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function targetUrl(User $user, int $assessmentId): string
    {
        $assessment = Assessment::select(['id', 'assessor_id', 'assessee_id'])->find($assessmentId);

        if (! $assessment) {
            return route('notifications.index');
        }

        $pid = $user->personnel?->id;

        return match (true) {
            $pid !== null && $pid === $assessment->assessor_id => route('assessor.assessments.show', $assessment->id),
            $user->can('assessments.approve') => route('review.show', $assessment->id),
            $pid !== null && $pid === $assessment->assessee_id && $user->isAssessor() => route('assessor.results.show', $assessment->id),
            $pid !== null && $pid === $assessment->assessee_id => route('member.results.show', $assessment->id),
            default => route('notifications.index'),
        };
    }
}
