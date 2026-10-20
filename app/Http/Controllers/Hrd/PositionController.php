<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\PositionRequest;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $positions = Position::withCount('assignments')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('hrd.positions.index', compact('positions'));
    }

    public function create(): View
    {
        return view('hrd.positions.form', ['position' => null]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        Position::create($data);

        return redirect()->route('hrd.positions.index')->with('success', 'Jabatan ditambahkan.');
    }

    public function edit(Position $position): View
    {
        return view('hrd.positions.form', compact('position'));
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $position->update($data);

        return redirect()->route('hrd.positions.index')->with('success', 'Jabatan diperbarui.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->assignments()->exists()) {
            return back()->with('error', 'Jabatan masih dipakai pada riwayat penempatan. Nonaktifkan saja.');
        }

        $position->delete();

        return redirect()->route('hrd.positions.index')->with('success', 'Jabatan dihapus.');
    }
}
