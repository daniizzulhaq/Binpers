<?php

namespace App\Http\Controllers\Hrd;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentAuthorizationRequest;
use App\Http\Requests\UnitAuthorizationRequest;
use App\Models\AssessmentAuthorization;
use App\Models\OrganizationalUnit;
use App\Models\Personnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssessmentAuthorizationController extends Controller
{
    public function index(Request $request): View
    {
        $authorizations = AssessmentAuthorization::with([
                'assessor.user', 'assessor.currentAssignment.unit',
                'assessee.user', 'assessee.currentAssignment.unit',
            ])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w
                    ->whereHas('assessor.user', fn ($u) => $u->where('name', 'like', $term))
                    ->orWhereHas('assessee.user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->latest()->paginate(15)->withQueryString();

        return view('hrd.authorizations.index', compact('authorizations'));
    }

    public function create(): View
    {
        return view('hrd.authorizations.form', [
            'authorization' => null,
            'assessors' => Personnel::with('user')
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', RoleName::canAssess()))->get(),
            'assessees' => Personnel::with(['user', 'currentAssignment.unit'])
                ->whereHas('user', fn ($q) => $q->where('is_active', true))->orderBy('nrp')->get(),
            'units' => OrganizationalUnit::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(AssessmentAuthorizationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data['assessee_ids'] as $assesseeId) {
            AssessmentAuthorization::updateOrCreate(
                ['assessor_id' => $data['assessor_id'], 'assessee_id' => $assesseeId],
                [
                    'valid_from' => $data['valid_from'] ?? null,
                    'valid_until' => $data['valid_until'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'is_active' => true,
                    'created_by' => $request->user()->id,
                ]
            );
        }

        return redirect()->route('hrd.authorizations.index')
            ->with('success', count($data['assessee_ids']).' kewenangan penilaian disimpan.');
    }

    /** Tetapkan seluruh anggota aktif pada sebuah unit (opsional beserta sub-unitnya) kepada satu penilai. */
    public function storeByUnit(UnitAuthorizationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $unitIds = $request->boolean('include_children')
            ? OrganizationalUnit::descendantIdsOf((int) $data['unit_id'])
            : [(int) $data['unit_id']];

        $assesseeIds = Personnel::query()
            ->whereHas('currentAssignment', fn ($q) => $q->whereIn('organizational_unit_id', $unitIds))
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->whereKeyNot($data['assessor_id'])
            ->pluck('id');

        if ($assesseeIds->isEmpty()) {
            return back()->withInput()->with('error', 'Tidak ada anggota aktif pada unit tersebut.');
        }

        DB::transaction(function () use ($assesseeIds, $data, $request) {
            foreach ($assesseeIds as $assesseeId) {
                AssessmentAuthorization::updateOrCreate(
                    ['assessor_id' => $data['assessor_id'], 'assessee_id' => $assesseeId],
                    [
                        'valid_from' => $data['valid_from'] ?? null,
                        'valid_until' => $data['valid_until'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'is_active' => true,
                        'created_by' => $request->user()->id,
                    ]
                );
            }
        });

        return redirect()->route('hrd.authorizations.index')
            ->with('success', $assesseeIds->count().' anggota ditetapkan kepada penilai.');
    }

    public function edit(AssessmentAuthorization $authorization): View
    {
        return view('hrd.authorizations.form', [
            'authorization' => $authorization->load(['assessor.user', 'assessee.user']),
        ]);
    }

    public function update(AssessmentAuthorizationRequest $request, AssessmentAuthorization $authorization): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $authorization->update($data);

        return redirect()->route('hrd.authorizations.index')->with('success', 'Kewenangan diperbarui.');
    }

    public function destroy(AssessmentAuthorization $authorization): RedirectResponse
    {
        abort_unless(auth()->user()->can('authorizations.manage'), 403);

        $authorization->delete();

        return back()->with('success', 'Kewenangan dihapus.');
    }
}
