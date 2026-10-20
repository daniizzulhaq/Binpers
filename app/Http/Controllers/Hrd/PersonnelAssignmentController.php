<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\PersonnelAssignmentRequest;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PersonnelAssignmentController extends Controller
{
    /** Tambah penempatan baru; penempatan aktif sebelumnya ditutup sehari sebelumnya. */
    public function store(PersonnelAssignmentRequest $request, Personnel $personnel): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($personnel, $data) {
            $personnel->assignments()->whereNull('end_date')->get()->each(
                fn (PersonnelAssignment $a) => $a->update(['end_date' => now()->parse($data['start_date'])->subDay()->toDateString()])
            );

            $personnel->assignments()->create($data);
        });

        return back()->with('success', 'Penempatan baru dicatat.');
    }

    public function destroy(PersonnelAssignment $assignment): RedirectResponse
    {
        abort_unless(auth()->user()->can('personnel.manage'), 403);

        $assignment->delete();

        return back()->with('success', 'Riwayat penempatan dihapus.');
    }
}
