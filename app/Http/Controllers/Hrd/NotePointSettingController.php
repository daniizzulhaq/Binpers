<?php

namespace App\Http\Controllers\Hrd;

use App\Enums\NoteLevel;
use App\Http\Controllers\Controller;
use App\Models\NotePointSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotePointSettingController extends Controller
{
    public function edit(): View
    {
        return view('hrd.note-points.edit', ['map' => NotePointSetting::map()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (['positive', 'negative'] as $type) {
            foreach (NoteLevel::cases() as $level) {
                $rules["points.{$type}.{$level->value}"] = ['required', 'integer', 'min:0', 'max:100'];
            }
        }

        $data = $request->validate($rules, [], ['points.*.*' => 'poin']);

        DB::transaction(function () use ($data) {
            foreach ($data['points'] as $type => $levels) {
                foreach ($levels as $level => $points) {
                    NotePointSetting::updateOrCreate(
                        ['type' => $type, 'level' => $level],
                        ['points' => (int) $points]
                    );
                }
            }
        });

        return back()->with('success', 'Poin tersimpan. Berlaku untuk catatan baru; catatan lama tetap memakai poin saat dibuat.');
    }
}
