<?php

namespace App\Http\Controllers\Hrd;

use App\Enums\PointType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PointAdjustmentRequest;
use App\Http\Requests\RewardRequest;
use App\Models\Personnel;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Services\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class RewardController extends Controller implements HasMiddleware
{
    public function __construct(private PointService $points)
    {
    }

    public static function middleware(): array
    {
        return ['permission:rewards.manage'];
    }

    public function index(): View
    {
        $rewards = Reward::withCount([
            'pointTransactions as granted_count' => fn ($q) => $q->where('type', PointType::Redeemed->value),
        ])->orderBy('min_points')->get();

        foreach ($rewards as $reward) {
            $reward->eligible_count = $this->points->eligibleQuery($reward)->count();
        }

        return view('hrd.rewards.index', compact('rewards'));
    }

    public function create(): View
    {
        return view('hrd.rewards.form', ['reward' => null]);
    }

    public function store(RewardRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        Reward::create($data);

        return redirect()->route('hrd.rewards.index')->with('success', 'Apresiasi ditambahkan.');
    }

    public function edit(Reward $reward): View
    {
        return view('hrd.rewards.form', compact('reward'));
    }

    public function update(RewardRequest $request, Reward $reward): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $reward->update($data);

        return redirect()->route('hrd.rewards.index')->with('success', 'Apresiasi diperbarui.');
    }

    public function destroy(Reward $reward): RedirectResponse
    {
        if ($reward->pointTransactions()->exists()) {
            return back()->with('error', 'Apresiasi sudah pernah diberikan. Nonaktifkan saja agar riwayat tetap utuh.');
        }

        $reward->delete();

        return redirect()->route('hrd.rewards.index')->with('success', 'Apresiasi dihapus.');
    }

    /** Daftar personel yang direkomendasikan untuk apresiasi ini, beserta riwayat pemberiannya. */
    public function recommendations(Request $request, Reward $reward): View
    {
        $eligible = $this->points->eligibleQuery($reward)
            ->with(['user', 'currentAssignment.unit', 'currentAssignment.position'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w->where('nrp', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->orderByDesc('total_points')
            ->paginate(15)->withQueryString();

        $granted = PointTransaction::with(['personnel.user'])
            ->where('reward_id', $reward->id)
            ->where('type', PointType::Redeemed->value)
            ->latest('id')->limit(20)->get();

        return view('hrd.rewards.recommendations', compact('reward', 'eligible', 'granted'));
    }

    public function grant(Request $request, Reward $reward, Personnel $personnel): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:255']]);

        $this->points->grant($reward, $personnel, $request->user(), $data['notes'] ?? null);

        return back()->with('success', "Apresiasi {$reward->name} dicatat untuk {$personnel->user->name}.");
    }

    /** Buku besar poin dengan filter personel dan jenis transaksi. */
    public function ledger(Request $request): View
    {
        $transactions = PointTransaction::with(['personnel.user', 'reward', 'assessment.period'])
            ->when($request->integer('personnel_id'), fn ($q, $id) => $q->where('personnel_id', $id))
            ->when(PointType::tryFrom((string) $request->input('type')), fn ($q, $type) => $q->where('type', $type->value))
            ->latest('id')->paginate(20)->withQueryString();

        return view('hrd.points.index', [
            'transactions' => $transactions,
            'types' => PointType::cases(),
            'personnel' => Personnel::with('user')->orderBy('nrp')->get(),
        ]);
    }

    public function adjust(PointAdjustmentRequest $request, Personnel $personnel): RedirectResponse
    {
        $data = $request->validated();

        $this->points->adjust($personnel, (int) $data['points'], $data['description'], $request->user());

        return back()->with('success', 'Penyesuaian poin dicatat.');
    }
}
