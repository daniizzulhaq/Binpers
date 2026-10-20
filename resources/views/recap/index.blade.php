@extends('layouts.app')

@section('title', 'Rekap Nilai Bulanan')
@section('page-title', 'Rekap Nilai Bulanan')

@section('content')
    @php
        $gradeColor = fn (?string $g) => match ($g) {
            'Sangat Baik' => 'success',
            'Baik' => 'primary',
            'Cukup' => 'warning',
            default => 'danger',
        };
    @endphp

    @if (! $period)
        <div class="alert alert-info">Belum ada periode penilaian.</div>
    @else
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="period_id" class="form-select" aria-label="Periode">
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}" @selected($p->id === $period->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="unit_id" class="form-select" aria-label="Unit">
                    <option value="">Semua unit</option>
                    @foreach ($units as $u)
                        <option value="{{ $u->id }}" @selected((int) request('unit_id') === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau NRP">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div>
                    <div class="fw-semibold">{{ $period->name }}</div>
                    <div class="small text-muted">
                        {{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}
                    </div>
                </div>

                @if ($period->isRecapLocked())
                    <span class="badge text-bg-dark">
                        <i class="bi bi-lock-fill me-1"></i>Dikunci {{ $period->recap_locked_at->format('d/m/Y H:i') }}
                        @if ($period->lockedBy) oleh {{ $period->lockedBy->name }} @endif
                    </span>
                @else
                    <span class="badge text-bg-warning">Sementara, nilai masih dapat berubah</span>

                    @can('periods.manage')
                        <form method="POST" action="{{ route('recap.lock', $period) }}" class="ms-auto"
                              onsubmit="return confirm('Kunci rekap {{ $period->name }}? Nilai akan disimpan permanen dan catatan di bulan ini tidak bisa diubah lagi.')">
                            @csrf
                            <button class="btn btn-outline-dark"><i class="bi bi-lock me-1"></i>Kunci Rekap</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Personel</th>
                            <th>Unit</th>
                            <th class="text-end">Penilaian</th>
                            <th class="text-end">Apresiasi</th>
                            <th class="text-end">Teguran</th>
                            <th class="text-end">Total</th>
                            <th>Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($people as $p)
                            @php
                                $r = $rows->get($p->id);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $p->user->name }}</div>
                                    <div class="small text-muted">{{ $p->nrp }}</div>
                                </td>
                                <td class="small">{{ $p->currentAssignment?->unit?->name ?? '-' }}</td>
                                <td class="text-end">
                                    @if ($r['base'] !== null)
                                        {{ number_format($r['base'], 2) }}
                                        <div class="small text-muted">{{ $r['assessments'] }} penilai</div>
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td class="text-end text-success">+{{ $r['bonus'] }}</td>
                                <td class="text-end" style="color:#b45309;">−{{ $r['penalty'] }}</td>
                                <td class="text-end fw-bold">
                                    {{ $r['total'] !== null ? number_format($r['total'], 2) : '–' }}
                                </td>
                                <td>
                                    @if ($r['grade'])
                                        <span class="badge text-bg-{{ $gradeColor($r['grade']) }}">{{ $r['grade'] }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">Belum dinilai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada personel.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $people->links() }}</div>
    @endif
@endsection
