@extends('layouts.app')

@section('title', 'Pemeriksaan Penilaian')
@section('page-title', 'Pemeriksaan Penilaian')

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected($activeStatus?->value === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="period_id" class="form-select">
                <option value="">Semua periode</option>
                @foreach ($periods as $p)
                    <option value="{{ $p->id }}" @selected((int) request('period_id') === $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button></div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Periode</th><th>Anggota</th><th>Unit</th><th>Penilai</th><th class="text-end">Nilai</th><th>Status</th><th>Dikirim</th><th></th></tr></thead>
                <tbody>
                    @forelse ($assessments as $a)
                        <tr>
                            <td>{{ $a->period->name }}</td>
                            <td><div class="fw-semibold">{{ $a->assessee->user->name }}</div><div class="small text-muted">{{ $a->assessee->nrp }}</div></td>
                            <td>{{ $a->assesseeUnit?->name ?? '—' }}</td>
                            <td>{{ $a->assessor->user->name }}</td>
                            <td class="text-end">{{ $a->final_score !== null ? number_format((float) $a->final_score, 2) : '—' }}</td>
                            <td><x-status-badge :status="$a->status" /></td>
                            <td class="small">{{ $a->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end"><a href="{{ route('review.show', $a) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty icon="clipboard-check">Tidak ada penilaian untuk filter ini.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $assessments->links() }}</div>
@endsection
