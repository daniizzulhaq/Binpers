{{-- Rekap triwulan / semester / tahunan: rata-rata nilai total bulanan dalam rentang. --}}
@php
    $months = \App\Http\Controllers\MonthlyRecapController::MONTHS;
    $partName = $type === 'triwulan' ? 'Triwulan' : 'Semester';
    $roman = [1 => 'I', 'II', 'III', 'IV'];
@endphp

<form method="GET" class="row g-2 mb-3">
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="col-md-2">
        <select name="year" class="form-select" aria-label="Tahun">
            @foreach ($years as $y)
                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    @if ($parts > 1)
        <div class="col-md-2">
            <select name="part" class="form-select" aria-label="{{ $partName }}">
                @for ($i = 1; $i <= $parts; $i++)
                    <option value="{{ $i }}" @selected($i === $part)>{{ $partName }} {{ $roman[$i] }}</option>
                @endfor
            </select>
        </div>
    @endif
    <div class="{{ $parts > 1 ? 'col-md-2' : 'col-md-3' }}">
        <select name="unit_id" class="form-select" aria-label="Unit">
            <option value="">Semua unit</option>
            @foreach ($units as $u)
                <option value="{{ $u->id }}" @selected((int) request('unit_id') === $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="{{ $parts > 1 ? 'col-md-2' : 'col-md-3' }}">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau NRP">
    </div>
    <div class="col-md-2">
        @include('recap._sort')
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100">Tampilkan</button>
    </div>
</form>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div>
            <div class="fw-semibold">{{ $label }}</div>
            <div class="small text-muted">{{ $monthRange }} · {{ $rangePeriods->count() }} periode penilaian</div>
        </div>

        @if ($rangePeriods->isEmpty())
            <span class="badge text-bg-secondary">Belum ada periode di rentang ini</span>
        @elseif ($lockedCount === $rangePeriods->count())
            <span class="badge text-bg-dark"><i class="bi bi-lock-fill me-1"></i>Semua bulan sudah dikunci</span>
        @else
            <span class="badge text-bg-warning">Sementara · {{ $lockedCount }} dari {{ $rangePeriods->count() }} bulan dikunci</span>
        @endif

        <div class="small text-muted ms-md-auto">
            Nilai = rata-rata nilai total bulanan yang sudah ada nilainya.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Personel</th>
                    <th>Unit</th>
                    @foreach ($rangePeriods as $rp)
                        <th class="text-end text-nowrap">
                            {{ $months[$rp->start_date->month] }}
                            @if ($rp->isRecapLocked()) <i class="bi bi-lock-fill small text-muted" title="Dikunci"></i> @endif
                        </th>
                    @endforeach
                    <th class="text-end">Apresiasi</th>
                    <th class="text-end">Teguran</th>
                    @include('recap._score-th', ['label' => 'Rata-rata'])
                    <th>Predikat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($people as $p)
                    @php
                        $r = $rows->get($p->id);
                        $detail = route('recap.show', ['personnel' => $p, 'type' => $type, 'year' => $year, 'part' => $part]);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ $detail }}" class="fw-semibold text-decoration-none">{{ $p->user->name }}</a>
                            <div class="small text-muted">{{ $p->nrp }}</div>
                        </td>
                        <td class="small">{{ $p->currentAssignment?->unit?->name ?? '-' }}</td>
                        @foreach ($rangePeriods as $rp)
                            @php $m = $r['months'][$rp->id] ?? null; @endphp
                            <td class="text-end">
                                @if ($m !== null)
                                    <a href="{{ route('recap.index', ['type' => 'bulanan', 'period_id' => $rp->id, 'q' => $p->nrp]) }}"
                                       class="text-decoration-none">{{ number_format($m, 2) }}</a>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-end text-success">+{{ $r['bonus'] ?? 0 }}</td>
                        <td class="text-end" style="color:var(--bs-danger);">−{{ $r['penalty'] ?? 0 }}</td>
                        <td class="text-end fw-bold">
                            @if (($r['total'] ?? null) !== null)
                                {{ number_format($r['total'], 2) }}
                                <div class="small text-muted fw-normal">{{ $r['scored'] }} bulan</div>
                            @else
                                –
                            @endif
                        </td>
                        <td>
                            @if ($r['grade'] ?? null)
                                <span class="badge text-bg-{{ $gradeColor($r['grade']) }}">{{ $r['grade'] }}</span>
                            @else
                                <span class="badge text-bg-secondary">Belum dinilai</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ $detail }}" class="btn btn-sm btn-outline-primary">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 7 + $rangePeriods->count() }}" class="text-center text-muted py-4">Tidak ada personel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $people->links() }}</div>
