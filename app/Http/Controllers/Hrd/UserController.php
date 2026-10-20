<?php

namespace App\Http\Controllers\Hrd;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with(['roles', 'personnel'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('username', 'like', $term));
            })
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')->value()))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('hrd.users.index', ['users' => $users, 'roles' => RoleName::cases()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $oldRoles = $user->getRoleNames()->all();

        // Cegah HRD mengunci dirinya sendiri.
        if ($user->is($request->user()) && (! $request->boolean('is_active') || $oldRoles !== [$data['role']])) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan atau mengubah role akun Anda sendiri.');
        }

        DB::transaction(function () use ($user, $data, $request) {
            $user->update(['is_active' => $request->boolean('is_active')]);
            $user->syncRoles([$data['role']]);
        });

        if ($oldRoles !== [$data['role']]) {
            AuditLog::record('roles_changed', $user, ['roles' => $oldRoles], ['roles' => [$data['role']]]);
        }

        return back()->with('success', "Akun {$user->username} diperbarui.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $password = Str::password(10, symbols: false);
        $user->update(['password' => $password]);

        AuditLog::record('password_reset', $user, [], ['reset_by' => auth()->id()]);

        return back()->with('success', "Kata sandi baru untuk {$user->username}: {$password} (catat sekarang, tidak ditampilkan lagi).");
    }
}
