@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan Penilaian')

@section('content')
    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Periode</label>
                <select name="period_id" class="form-select">
                    <option value="">Semua periode</option>
                    @foreach ($periods as $p)<option value="{{ $p->id }}" @selected((int) $filters['period_id'] === $p->id)>{{ $p->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Unit</label>
                <select name="unit_id" class="form-select">
                    <option value="">Semua unit</option>
                    @foreach ($units as $u)<option value="{{ $u->id }}" @selected((int) $filters['unit_id'] === $u->id)>{{ $u->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Jabatan</label>
                <select name="position_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach ($positions as $pos)<option value="{{ $pos->id }}" @selected((int) $filters['position_id'] === $pos->id)>{{ $pos->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>{{ $s->label() }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between">
            <span class="fw-semibold">Hasil</span><span class="text-muted small">{{ $assessments->total() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Periode</th><th>Anggota</th><th>Unit</th><th>Jabatan</th><th>Penilai</th><th class="text-end">Nilai</th><th>Predikat</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($assessments as $a)
                        <tr>
                            <td>{{ $a->period->name }}</td>
                            <td><div class="fw-semibold">{{ $a->assessee->user->name }}</div><div class="small text-muted">{{ $a->assessee->nrp }}</div></td>
                            <td>{{ $a->assesseeUnit?->name ?? '—' }}</td>
                            <td>{{ $a->assesseePosition?->name ?? '—' }}</td>
                            <td>{{ $a->assessor->user->name }}</td>
                            <td class="text-end">{{ $a->final_score !== null ? number_format((float) $a->final_score, 2) : '—' }}</td>
                            <td>{{ $a->grade ?? '—' }}</td>
                            <td><x-status-badge :status="$a->status" /></td>
                            <td class="text-end"><a href="{{ route('review.show', $a) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-empty icon="file-earmark-bar-graph">Tidak ada data untuk filter ini.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $assessments->links() }}</div>
@endsection
