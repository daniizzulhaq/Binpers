<?php

namespace App\Http\Controllers\Hrd;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\PersonnelRequest;
use App\Models\OrganizationalUnit;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersonnelController extends Controller
{
    private const PERSONNEL_FIELDS = ['nrp', 'rank', 'gender', 'place_of_birth', 'birth_date', 'join_date', 'phone', 'address'];

    public function index(Request $request): View
    {
        $personnel = Personnel::with(['user.roles', 'currentAssignment.unit', 'currentAssignment.position'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w->where('nrp', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('username', 'like', $term)));
            })
            ->when($request->integer('unit_id'), fn ($q, $unit) => $q->whereHas(
                'currentAssignment',
                fn ($a) => $a->whereIn('organizational_unit_id', OrganizationalUnit::descendantIdsOf($unit))
            ))
            ->orderBy('nrp')
            ->paginate(15)->withQueryString();

        return view('hrd.personnel.index', [
            'personnel' => $personnel,
            'units' => OrganizationalUnit::orderBy('code')->get(),
        ]);
    }

    public function create(): View
    {
        return view('hrd.personnel.form', $this->formData(null));
    }

    public function store(PersonnelRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $personnel = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->assignRole($data['role']);

            $personnel = Personnel::create(['user_id' => $user->id] + Arr::only($data, self::PERSONNEL_FIELDS));

            if (! empty($data['organizational_unit_id'])) {
                $personnel->assignments()->create([
                    'organizational_unit_id' => $data['organizational_unit_id'],
                    'position_id' => $data['position_id'],
                    'start_date' => $data['join_date'] ?? today()->toDateString(),
                ]);
            }

            return $personnel;
        });

        return redirect()->route('hrd.personnel.show', $personnel)->with('success', 'Akun dan data personel berhasil dibuat.');
    }

    public function show(Personnel $personnel): View
    {
        $personnel->load(['user.roles', 'assignments.unit', 'assignments.position']);

        return view('hrd.personnel.show', [
            'personnel' => $personnel,
            'units' => OrganizationalUnit::where('is_active', true)->orderBy('code')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
            'points' => $personnel->totalPoints(),
        ]);
    }

    public function edit(Personnel $personnel): View
    {
        return view('hrd.personnel.form', $this->formData($personnel->load('user')));
    }

    public function update(PersonnelRequest $request, Personnel $personnel): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($personnel, $data) {
            $userData = Arr::only($data, ['name', 'username', 'email']);
            if (! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            $personnel->user->update($userData);
            $personnel->update(Arr::only($data, self::PERSONNEL_FIELDS));
        });

        return redirect()->route('hrd.personnel.show', $personnel)->with('success', 'Data personel diperbarui.');
    }

    public function destroy(Personnel $personnel): RedirectResponse
    {
        if ($personnel->assessmentsAsAssessee()->exists() || $personnel->assessmentsAsAssessor()->exists()) {
            return back()->with('error', 'Personel sudah memiliki riwayat penilaian. Nonaktifkan akunnya, jangan dihapus.');
        }

        DB::transaction(function () use ($personnel) {
            $user = $personnel->user;
            $personnel->delete();
            $user->delete();
        });

        return redirect()->route('hrd.personnel.index')->with('success', 'Personel dan akunnya dihapus.');
    }

    private function formData(?Personnel $personnel): array
    {
        return [
            'personnel' => $personnel,
            'roles' => RoleName::cases(),
            'ranks' => config('assessment.ranks'),
            'units' => OrganizationalUnit::where('is_active', true)->orderBy('code')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
