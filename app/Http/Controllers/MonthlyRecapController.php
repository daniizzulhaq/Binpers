<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Models\OrganizationalUnit;
use App\Models\PerformanceNote;
use App\Models\Personnel;
use App\Models\User;
use App\Services\MonthlyScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MonthlyRecapController extends Controller
{
    /** Jenis rekap => [label, jumlah bulan per bagian]. */
    public const TYPES = [
        'bulanan' => ['Bulanan', 1],
        'triwulan' => ['Triwulan', 3],
        'semester' => ['Semester', 6],
        'tahunan' => ['Tahunan', 12],
    ];

    public const SORTS = [
        'nrp' => 'NRP',
        'nama' => 'Nama (A–Z)',
        'tertinggi' => 'Nilai tertinggi',
        'terendah' => 'Nilai terendah',
    ];

    public const MONTHS =[1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function __construct(private MonthlyScoreService $scores)
    {
    }

    public function index(Request $request): View
    {
        $periods = AssessmentPeriod::orderByDesc('start_date')->get();
        $type = array_key_exists($request->query('type'), self::TYPES) ? $request->query('type') : 'bulanan';

        $sort = array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'nrp';

        $ids = $this->scores->scoredPersonnelQuery()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w->where('nrp', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->when($request->integer('unit_id'), fn ($q, $unit) => $q->whereHas(
                'currentAssignment',
                fn ($a) => $a->whereIn('organizational_unit_id', OrganizationalUnit::descendantIdsOf($unit))
            ))
            ->when($sort === 'nama', fn ($q) => $q->orderBy(
                User::select('name')->whereColumn('users.id', 'personnel.user_id')
            ))
            ->orderBy('nrp')
            ->pluck('personnel.id');

        // Nilai dihitung untuk semua personel yang tersaring agar urutan nilai berlaku lintas halaman.
        if ($type === 'bulanan') {
            $period = $request->integer('period_id')
                ? $periods->firstWhere('id', $request->integer('period_id'))
                : ($this->scores->currentPeriod() ?? $periods->first());

            $period?->loadMissing('lockedBy');

            $extra = ['period' => $period, 'rows' => $period ? $this->scores->compute($period, $ids) : collect()];
        } else {
            $extra = $this->range($request, $type, $periods, $ids);
        }

        if (in_array($sort, ['tertinggi', 'terendah'], true)) {
            $ids = $this->sortByScore($ids, $extra['rows'], $sort === 'tertinggi');
        }

        return view('recap.index', $extra + [
            'types' => self::TYPES,
            'type' => $type,
            'sorts' => self::SORTS,
            'sort' => $sort,
            'periods' => $periods,
            'people' => $this->paginateIds($request, $ids),
            'units' => OrganizationalUnit::orderBy('code')->get(),
        ]);
    }

    /** Urutkan menurut nilai total; yang belum dinilai selalu di bawah. */
    private function sortByScore(Collection $ids, Collection $rows, bool $desc): Collection
    {
        return $ids->values()
            ->map(fn ($id, $i) => [$id, $rows->get($id)['total'] ?? null, $i])
            ->sort(function ($a, $b) use ($desc) {
                if ($a[1] === null || $b[1] === null) {
                    return [$a[1] === null, $a[2]] <=> [$b[1] === null, $b[2]];
                }

                return ($desc ? $b[1] <=> $a[1] : $a[1] <=> $b[1]) ?: $a[2] <=> $b[2];
            })
            ->pluck(0)->values();
    }

    /** Halaman personel sesuai urutan id yang sudah ditentukan. */
    private function paginateIds(Request $request, Collection $ids): LengthAwarePaginator
    {
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $ids->forPage($page, $perPage)->values();

        $models = Personnel::with(['user', 'currentAssignment.unit'])->whereKey($slice)->get()->keyBy('id');

        return (new LengthAwarePaginator(
            $slice->map(fn ($id) => $models->get($id))->filter()->values(),
            $ids->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        ))->withQueryString();
    }

    /** Detail rekap satu personel: nilai per bulan, rekap dari setiap penilai, dan semua catatannya. */
    public function show(Request $request, Personnel $personnel): View
    {
        abort_unless($this->scores->scoredPersonnelQuery()->whereKey($personnel->id)->exists(), 404);

        $periods = AssessmentPeriod::orderByDesc('start_date')->get();
        $type = array_key_exists($request->query('type'), self::TYPES) ? $request->query('type') : 'bulanan';

        if ($type === 'bulanan') {
            $period = $periods->firstWhere('id', $request->integer('period_id'))
                ?? $this->scores->currentPeriod() ?? $periods->first();
            $range = [
                'label' => $period?->name ?? '-',
                'monthRange' => $period ? $period->start_date->format('d/m/Y').' – '.$period->end_date->format('d/m/Y') : '',
                'rangePeriods' => $period ? collect([$period]) : collect(),
            ];
        } else {
            $range = $this->range($request, $type, $periods, collect([$personnel->id]));
        }

        $selected = $range['rangePeriods'];
        $ids = collect([$personnel->id]);
        $monthly = $selected->mapWithKeys(fn ($p) => [$p->id => $this->scores->compute($p, $ids)->get($personnel->id)]);
        $summary = $this->scores->aggregate($selected, $ids)->get($personnel->id);

        $assessments = Assessment::with(['period', 'assessor.user', 'assessor.currentAssignment.position'])
            ->where('assessee_id', $personnel->id)
            ->whereIn('assessment_period_id', $selected->pluck('id'))
            ->get()
            ->sortBy(fn ($a) => $a->period->start_date->format('Ymd').'|'.($a->assessor?->user?->name ?? ''))
            ->values();

        $notes = $selected->isEmpty() ? collect() : PerformanceNote::with([
            'authorUser', 'author.user', 'author.currentAssignment.position', 'criterion',
        ])
            ->where('subject_id', $personnel->id)
            ->whereBetween('incident_date', [
                $selected->min('start_date')->toDateString(),
                $selected->max('end_date')->toDateString(),
            ])
            ->orderByDesc('incident_date')->orderByDesc('id')
            ->get();

        return view('recap.show', $range + [
            'personnel' => $personnel->load(['user', 'currentAssignment.unit', 'currentAssignment.position']),
            'type' => $type,
            'typeLabel' => self::TYPES[$type][0],
            'monthly' => $monthly,
            'summary' => $summary,
            'assessments' => $assessments,
            'notes' => $notes,
            'backQuery' => $request->only(['type', 'period_id', 'year', 'part', 'sort']),
        ]);
    }

    public function lock(Request $request, AssessmentPeriod $period): RedirectResponse
    {
        $count = $this->scores->lock($period, $request->user());

        return back()->with('success', "Rekap {$period->name} dikunci untuk {$count} personel.");
    }

    /** Data rekap triwulan/semester/tahunan untuk tahun & bagian yang dipilih. */
    private function range(Request $request, string $type, Collection $periods, Collection $ids): array
    {
        $size = self::TYPES[$type][1];
        $parts = intdiv(12, $size);
        $current = $this->scores->currentPeriod()?->start_date ?? today();

        $years = $periods->map(fn ($p) => $p->start_date->year)->unique()->sortDesc()->values();
        $year = $years->contains($request->integer('year')) ? $request->integer('year') : ($years->first() ?? $current->year);

        $part = $request->integer('part');
        if ($part < 1 || $part > $parts) {
            $part = $year === $current->year ? intdiv($current->month - 1, $size) + 1 : 1;
        }

        $fromMonth = ($part - 1) * $size + 1;
        $toMonth = $fromMonth + $size - 1;

        // Bulan-bulan dalam rentang, berurutan; bulan tanpa periode tetap ditampilkan kosong.
        $inRange = $periods
            ->filter(fn ($p) => $p->start_date->year === $year
                && $p->start_date->month >= $fromMonth && $p->start_date->month <= $toMonth)
            ->sortBy('start_date')->values();

        $label = match ($type) {
            'triwulan' => "Triwulan {$this->roman($part)} {$year}",
            'semester' => "Semester {$this->roman($part)} {$year}",
            default => "Tahun {$year}",
        };

        return [
            'years' => $years,
            'year' => $year,
            'parts' => $parts,
            'part' => $part,
            'label' => $label,
            'monthRange' => self::MONTHS[$fromMonth].' – '.self::MONTHS[$toMonth].' '.$year,
            'rangePeriods' => $inRange,
            'lockedCount' => $inRange->filter->isRecapLocked()->count(),
            'rows' => $this->scores->aggregate($inRange, $ids),
        ];
    }

    private function roman(int $n): string
    {
        return [1 => 'I', 'II', 'III', 'IV'][$n];
    }
}
