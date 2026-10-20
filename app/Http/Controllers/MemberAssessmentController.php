<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MemberAssessmentController extends Controller
{
    /** Hasil penilaian pribadi: hanya milik sendiri dan hanya yang sudah final. */
    public function index(Request $request): View
    {
        $personnel = $request->user()->personnel;
        abort_if($personnel === null, 403, 'Data personel Anda belum dibuat. Hubungi HRD.');

        $assessments = Assessment::with('period:id,name,start_date,end_date')
            ->select(['id', 'assessment_period_id', 'final_score', 'grade', 'member_notes', 'finalized_at'])
            ->where('assessee_id', $personnel->id)
            ->where('status', AssessmentStatus::Finalized->value)
            ->latest('finalized_at')->paginate(10);

        return view('member.assessments.index', compact('assessments'));
    }

    public function show(Assessment $assessment): View
    {
        Gate::authorize('view', $assessment);

        $assessment->load(['period', 'details.criterion']);

        $result = [
            'id' => $assessment->id,
            'period' => $assessment->period->name,
            'final_score' => $assessment->final_score,
            'grade' => $assessment->grade,
            'member_notes' => $assessment->member_notes,
            'finalized_at' => $assessment->finalized_at,
        ];

        $rows = $assessment->details
            ->sortBy(fn ($d) => $d->criterion->sort_order)
            ->map(fn ($d) => [
                'criterion' => $d->criterion->name,
                'weight' => $d->criterion->weight,
                'min' => $d->criterion->min_score,
                'max' => $d->criterion->max_score,
                'score' => $d->score,
            ])->values();

        return view('member.assessments.show', compact('result', 'rows'));
    }
}
