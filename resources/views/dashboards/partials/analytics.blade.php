@php
    $s = $data['summary'];
    $statusEnums = \App\Enums\AssessmentStatus::cases();
    $gradeLabels = collect(config('assessment.grades'))->pluck('label')->reverse()->values();

    $chart = [
        'trend' => [
            'labels' => $data['trend']->pluck('name')->all(),
            'values' => $data['trend']->map(fn ($t) => round((float) $t->avg_score, 2))->all(),
        ],
        'dist' => [
            'labels' => $gradeLabels->all(),
            'values' => $gradeLabels->map(fn ($g) => (int) $data['distribution']->get($g, 0))->all(),
        ],
        'status' => [
            'labels' => collect($statusEnums)->map(fn ($e) => $e->label())->all(),
            'values' => collect($statusEnums)->map(fn ($e) => (int) $data['statusCounts']->get($e->value, 0))->all(),
        ],
    ];
    $reportQuery = array_filter(request()->only(['period_id', 'unit_id', 'position_id']));
@endphp

{{-- Filter --}}
<form method="GET" action="{{ $action }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">Periode</label>
            <select name="period_id" class="form-select">
                @foreach ($periods as $p)
                    <option value="{{ $p->id }}" @selected((int) $filters['period_id'] === $p->id)>{{ $p->name }} ({{ $p->status->label() }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Unit</label>
            <select name="unit_id" class="form-select">
                <option value="">Semua unit</option>
                @foreach ($units as $u)
                    <option value="{{ $u->id }}" @selected((int) $filters['unit_id'] === $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Jabatan</label>
            <select name="position_id" class="form-select">
                <option value="">Semua jabatan</option>
                @foreach ($positions as $pos)
                    <option value="{{ $pos->id }}" @selected((int) $filters['position_id'] === $pos->id)>{{ $pos->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            <a href="{{ $action }}" class="btn btn-outline-secondary">Reset</a>
            <a href="{{ route('reports.index', $reportQuery) }}" class="btn btn-outline-primary" title="Laporan dengan filter status">
                <i class="bi bi-file-earmark-bar-graph"></i>
            </a>
        </div>
    </div>
</form>

@if ($periods->isEmpty())
    <div class="alert alert-info">Belum ada periode penilaian. @if ($isHrd) <a href="{{ route('hrd.periods.create') }}">Buat periode</a>.@endif</div>
@endif

{{-- Ringkasan --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-2"><x-stat-card icon="people" label="Personel aktif" :value="$s['personnel']" /></div>
    <div class="col-6 col-xl-2"><x-stat-card icon="clipboard-data" label="Total penilaian" :value="$s['assessments']" color="info" /></div>
    <div class="col-6 col-xl-2"><x-stat-card icon="patch-check" label="Final" :value="$s['finalized']" color="success" /></div>
    <div class="col-6 col-xl-2"><x-stat-card icon="hourglass-split" label="Menunggu pemeriksaan" :value="$s['pending']" color="warning" /></div>
    <div class="col-6 col-xl-2"><x-stat-card icon="speedometer" label="Rata-rata nilai final" :value="$s['average'] ?: '—'" color="primary" /></div>
    <div class="col-6 col-xl-2"><x-stat-card icon="graph-up-arrow" label="Progres penilaian" :value="$s['progress'].'%'" color="secondary" /></div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between small mb-1">
            <span class="fw-semibold">Progres terkirim terhadap kewenangan aktif</span><span>{{ $s['progress'] }}%</span>
        </div>
        <div class="progress" role="progressbar" aria-valuenow="{{ $s['progress'] }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $s['progress'] }}%"></div>
        </div>
    </div>
</div>

{{-- Grafik --}}
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Tren rata-rata nilai final per periode</div>
            <div class="card-body">
                @if (count($chart['trend']['labels']))
                    <div class="chart-box"><canvas id="chartTrend"></canvas></div>
                @else
                    <x-empty icon="graph-up">Belum ada penilaian final.</x-empty>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Distribusi predikat (final)</div>
            <div class="card-body">
                @if (array_sum($chart['dist']['values']) > 0)
                    <div class="chart-box"><canvas id="chartDist"></canvas></div>
                @else
                    <x-empty icon="pie-chart">Belum ada penilaian final.</x-empty>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Jumlah penilaian per status</div>
            <div class="card-body">
                <div class="chart-box" style="height: 220px"><canvas id="chartStatus"></canvas></div>
            </div>
        </div>
    </div>
</div>

{{-- Rekap unit --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Rekap berdasarkan unit organisasi</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Unit</th><th class="text-end">Penilaian</th><th class="text-end">Final</th><th style="min-width:160px">Kelengkapan final</th><th class="text-end">Rata-rata</th></tr>
            </thead>
            <tbody>
                @forelse ($data['unitRecap'] as $row)
                    @php($pct = $row->total ? round($row->finalized / $row->total * 100) : 0)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td class="text-end">{{ $row->total }}</td>
                        <td class="text-end">{{ (int) $row->finalized }}</td>
                        <td>
                            <div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width: {{ $pct }}%"></div></div>
                        </td>
                        <td class="text-end">{{ $row->avg_score !== null ? number_format($row->avg_score, 2) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="diagram-3">Belum ada data untuk filter ini.</x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Menunggu pemeriksaan --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Penilaian menunggu pemeriksaan</span>
        <a href="{{ route('review.index') }}" class="small">Lihat semua</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Anggota</th><th>Unit</th><th>Penilai</th><th>Dikirim</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($data['pending'] as $a)
                    <tr>
                        <td>{{ $a->assessee->user->name }}</td>
                        <td>{{ $a->assesseeUnit?->name ?? '—' }}</td>
                        <td>{{ $a->assessor->user->name }}</td>
                        <td>{{ $a->submitted_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-end"><a href="{{ route('review.show', $a) }}" class="btn btn-sm btn-outline-primary">Periksa</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="check2-circle">Tidak ada penilaian yang menunggu.</x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const d = @json($chart);
            const navy = '#0b2a5b';
            const el = (id) => document.getElementById(id);

            if (el('chartTrend')) {
                new Chart(el('chartTrend'), {
                    type: 'line',
                    data: { labels: d.trend.labels, datasets: [{ label: 'Rata-rata nilai', data: d.trend.values, borderColor: navy, backgroundColor: 'rgba(11,42,91,.1)', fill: true, tension: .3 }] },
                    options: { maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } }, plugins: { legend: { display: false } } },
                });
            }
            if (el('chartDist')) {
                new Chart(el('chartDist'), {
                    type: 'doughnut',
                    data: { labels: d.dist.labels, datasets: [{ data: d.dist.values, backgroundColor: ['#adb5bd', '#6c8ebf', '#2f5ea8', '#0b2a5b'] }] },
                    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
                });
            }
            new Chart(el('chartStatus'), {
                type: 'bar',
                data: { labels: d.status.labels, datasets: [{ data: d.status.values, backgroundColor: navy, borderRadius: 6 }] },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
            });
        });
    </script>
@endpush
