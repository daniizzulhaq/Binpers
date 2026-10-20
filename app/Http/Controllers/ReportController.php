<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Models\AssessmentPeriod;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): View
    {
        $filters = $analytics->filters($request);

        $assessments = $analytics->baseQuery($filters)
            ->with(['period', 'assessor.user', 'assessee.user', 'assesseeUnit', 'assesseePosition'])
            ->latest('assessments.updated_at')
            ->paginate(15)->withQueryString();

        return view('reports.index', [
            'filters' => $filters,
            'assessments' => $assessments,
            'periods' => AssessmentPeriod::orderByDesc('start_date')->get(),
            'units' => OrganizationalUnit::orderBy('code')->get(),
            'positions' => Position::orderBy('name')->get(),
            'statuses' => AssessmentStatus::cases(),
        ]);
    }
}
