<?php

namespace App\Providers;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Models\PerformanceNote;
use App\Services\AssessorScopeService;
use App\Services\MonthlyScoreService;
use App\Services\ReviewRoutingService;
use Illuminate\Pagination\Paginator;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Pagination menggunakan Bootstrap 5.
        Paginator::useBootstrapFive();

        // Gunakan HTTPS hanya jika request datang melalui proxy HTTPS.
        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        // Data beranda penilai (Kabag, Kasat, Kasi, Kapolsek, Wakapolsek, Kanit).
        View::composer('dashboards.assessor', function ($view) {
            $me = auth()->user()?->personnel;

            if (! $me) {
                $view->with('ah', null);

                return;
            }

            $me->loadMissing([
                'currentAssignment.unit',
                'currentAssignment.position',
            ]);

            $period = AssessmentPeriod::open()->first();

            // Antrean persetujuan untuk Kabag, Kasat, Kasi, Kasi Pengawas, dan Kapolsek.
            $reviews = null;

            if (auth()->user()->can('assessments.review-structural')) {
                $queue = app(ReviewRoutingService::class)->queueFor(auth()->user());

                $reviews = [
                    'count' => (clone $queue)->count(),
                    'top' => $queue->with(['assessee.user', 'assessor.user', 'period:id,name'])
                        ->latest('submitted_at')
                        ->limit(3)
                        ->get(),
                ];
            }

            $status = $period
                ? Assessment::where('assessment_period_id', $period->id)
                    ->where('assessor_id', $me->id)
                    ->toBase()
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                : collect();

            $view->with('ah', [
                'me' => $me,
                'period' => $period,
                // Anggota yang wajib dinilai menurut aturan role.
                'binaan' => app(AssessorScopeService::class)
                    ->mandatoryQuery($me)
                    ->count(),
                'status' => $status,
                'reviews' => $reviews,
                'revisions' => Assessment::with(['assessee.user', 'period:id,name'])
                    ->where('assessor_id', $me->id)
                    ->where('status', AssessmentStatus::Revision->value)
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(),
                'notesMonth' => PerformanceNote::where('author_id', $me->id)
                    ->whereDate('incident_date', '>=', now()->startOfMonth())
                    ->count(),
                'recentGiven' => PerformanceNote::with('subject.user')
                    ->where('author_id', $me->id)
                    ->latest('incident_date')
                    ->latest('id')
                    ->limit(3)
                    ->get(),
            ]);
        });

        // Data beranda anggota: nilai bulanan (penilaian + catatan kinerja).
        View::composer('dashboards.member', function ($view) {
            $user = auth()->user();
            $me = $user?->personnel;

            if (! $me) {
                $view->with('home', null);

                return;
            }

            $me->loadMissing([
                'currentAssignment.unit',
                'currentAssignment.position',
            ]);

            $scores = app(MonthlyScoreService::class);

            $periods = AssessmentPeriod::whereDate('start_date', '<=', today())
                ->orderByDesc('start_date')
                ->limit(4)
                ->get();

            $current = $periods->first();

            $history = $periods->map(fn ($p) => ['period' => $p] + $scores->forPersonnel($p, $me));

            $noteCounts = PerformanceNote::approved()->where('subject_id', $me->id)
                ->when($current, fn ($q) => $q->whereBetween('incident_date', [
                    $current->start_date->toDateString(),
                    $current->end_date->toDateString(),
                ]))
                ->toBase()
                ->selectRaw('type, COUNT(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type');

            $view->with('home', [
                'me' => $me,
                'current' => $current,
                'score' => $history->first(),
                'history' => $history,
                'noteCounts' => $noteCounts,
                'unread' => $user->unreadNotifications()->count(),
            ]);
        });

        // Catatan terbaru untuk pengguna yang sedang login
        // (beranda anggota/penilai).
        View::composer('partials.my-recent-notes', function ($view) {
            $me = auth()->user()?->personnel;

            $view->with('myNotes', $me
                ? PerformanceNote::with([
                    'authorUser',
                    'author.currentAssignment.position',
                    'criterion',
                ])
                    ->approved()
                    ->where('subject_id', $me->id)
                    ->latest('incident_date')
                    ->latest('id')
                    ->limit(5)
                    ->get()
                : collect());
        });

        // Panel catatan terbaru untuk HRD / Kapolres / Wakapolres.
        View::composer('dashboards.partials.recent-notes', function ($view) {
            if (! auth()->user()?->can('reports.view')) {
                $view->with([
                    'noteCounts' => collect(),
                    'recentNotes' => collect(),
                ]);

                return;
            }

            $view->with([
                'noteCounts' => PerformanceNote::toBase()
                    ->whereDate('incident_date', '>=', now()->subDays(30))
                    ->selectRaw('type, COUNT(*) as total')
                    ->groupBy('type')
                    ->pluck('total', 'type'),

                'recentNotes' => PerformanceNote::with([
                    'authorUser',
                    'author.currentAssignment.position',
                    'subject.user',
                    'unit',
                    'criterion',
                ])
                    ->latest('incident_date')
                    ->latest('id')
                    ->limit(8)
                    ->get(),
            ]);
        });
    }
}
