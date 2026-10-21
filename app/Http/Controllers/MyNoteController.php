<?php

namespace App\Http\Controllers;

use App\Enums\NoteType;
use App\Models\PerformanceNote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyNoteController extends Controller
{
    /** Catatan kinerja yang ditujukan kepada pengguna yang sedang login. */
    public function index(Request $request): View
    {
        $me = $request->user()->personnel;
        abort_if($me === null, 403, 'Data personel Anda belum dibuat. Hubungi HRD.');

        $base = PerformanceNote::query()
            ->where('subject_id', $me->id)
            ->when(NoteType::tryFrom((string) $request->input('type')), fn ($q, $t) => $q->where('type', $t->value));

        $counts = PerformanceNote::where('subject_id', $me->id)
            ->toBase()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $notes = $base
            ->with(['authorUser', 'author.currentAssignment.position', 'criterion'])
            ->latest('incident_date')->latest('id')
            ->paginate(10)->withQueryString();

        return view('notes.mine', [
            'notes' => $notes,
            'counts' => $counts,
            'types' => NoteType::cases(),
        ]);
    }
}
