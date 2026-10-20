<?php

namespace App\Http\Controllers\Hrd;

use App\Enums\UnitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationalUnitRequest;
use App\Models\OrganizationalUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationalUnitController extends Controller
{
    public function index(Request $request): View
    {
        $units = OrganizationalUnit::with('parent')->withCount(['children', 'assignments'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->orderBy('code')->paginate(15)->withQueryString();

        return view('hrd.units.index', compact('units'));
    }

    public function create(): View
    {
        return view('hrd.units.form', $this->formData(null));
    }

    public function store(OrganizationalUnitRequest $request): RedirectResponse
{
    $data = $request->validated();
    $data['is_active'] = $request->boolean('is_active');

    OrganizationalUnit::create($data);

    return redirect()->route('hrd.units.index')->with('success', 'Unit organisasi ditambahkan.');
}

    public function edit(OrganizationalUnit $unit): View
    {
        return view('hrd.units.form', $this->formData($unit));
    }

   public function update(OrganizationalUnitRequest $request, OrganizationalUnit $unit): RedirectResponse
{
    $data = $request->validated();
    $data['is_active'] = $request->boolean('is_active');

    $unit->update($data);

    return redirect()->route('hrd.units.index')->with('success', 'Unit organisasi diperbarui.');
}

    public function destroy(OrganizationalUnit $unit): RedirectResponse
    {
        if ($unit->children()->exists() || $unit->assignments()->exists()) {
            return back()->with('error', 'Unit masih memiliki sub-unit atau riwayat penempatan. Nonaktifkan saja.');
        }

        $unit->delete();

        return redirect()->route('hrd.units.index')->with('success', 'Unit organisasi dihapus.');
    }

    private function formData(?OrganizationalUnit $unit): array
    {
        return [
            'unit' => $unit,
            'types' => UnitType::cases(),
            'parents' => OrganizationalUnit::when($unit, fn ($q) => $q->whereKeyNot($unit->id))->orderBy('code')->get(),
        ];
    }
}
