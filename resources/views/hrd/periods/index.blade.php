@extends('layouts.app')

@section('title', 'Periode Penilaian')
@section('page-title', 'Periode & Kriteria')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('hrd.periods.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Periode</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Periode</th><th>Rentang</th><th>Status</th><th class="text-end">Kriteria</th><th class="text-end">Penilaian</th><th></th></tr></thead>
                <tbody>
                    @forelse ($periods as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->name }}</td>
                            <td class="small">{{ $p->start_date->format('d/m/Y') }} – {{ $p->end_date->format('d/m/Y') }}</td>
                            <td><x-status-badge :status="$p->status" /></td>
                            <td class="text-end">{{ $p->criteria_count }}</td>
                            <td class="text-end">{{ $p->assessments_count }}</td>
                            <td class="text-end"><a href="{{ route('hrd.periods.show', $p) }}" class="btn btn-sm btn-outline-primary">Kelola</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="calendar-range">Belum ada periode.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $periods->links() }}</div>
@endsection
