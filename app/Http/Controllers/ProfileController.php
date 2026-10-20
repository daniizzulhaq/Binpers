<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load([
            'personnel.currentAssignment.unit',
            'personnel.currentAssignment.position',
        ]);

        return view('profile.show', compact('user'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed', 'different:current_password'],
            ],
            [
                'current_password.current_password' => 'Kata sandi saat ini salah.',
                'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            ],
            [
                'current_password' => 'kata sandi saat ini',
                'password' => 'kata sandi baru',
            ]
        );

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
