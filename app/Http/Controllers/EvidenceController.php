<?php

namespace App\Http\Controllers;

use App\Models\AssessmentDetail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function show(AssessmentDetail $detail): StreamedResponse
    {
        $detail->load('assessment');

        Gate::authorize('viewEvidence', $detail->assessment);

        $disk = Storage::disk('local');

        abort_unless($detail->evidence_path && $disk->exists($detail->evidence_path), 404);

        return $disk->response($detail->evidence_path, null, ['X-Content-Type-Options' => 'nosniff']);
    }
}
