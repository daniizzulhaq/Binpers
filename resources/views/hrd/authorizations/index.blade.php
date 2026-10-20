@extends('layouts.app')

@section('title', 'Kewenangan Penilai')
@section('page-title', 'Kewenangan Penilai')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama penilai / anggota">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('hrd.authorizations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tetapkan Kewenangan</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Penilai</th><th>Anggota dinilai</th><th>Masa berlaku</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($authorizations as $a)
                        @php($cross = $a->assessor->currentAssignment?->organizational_unit_id !== $a->assessee->currentAssignment?->organizational_unit_id)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $a->assessor->user->name }}</div>
                                <div class="small text-muted">{{ $a->assessor->currentAssignment?->unit?->name ?? '—' }}</div>
                            </td>
                            <td>
                                <div>{{ $a->assessee->user->name }} @if ($cross)<span class="badge text-bg-info">Lintas unit</span>@endif</div>
                                <div class="small text-muted">{{ $a->assessee->currentAssignment?->unit?->name ?? '—' }}</div>
                            </td>
                            <td class="small">{{ $a->valid_from?->format('d/m/Y') ?? 'Tanpa batas' }} – {{ $a->valid_until?->format('d/m/Y') ?? 'Tanpa batas' }}</td>
                            <td>{!! $a->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('hrd.authorizations.edit', $a) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('hrd.authorizations.destroy', $a) }}" class="d-inline" onsubmit="return confirm('Hapus kewenangan ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="person-check">Belum ada kewenangan penilaian.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $authorizations->links() }}</div>
@endsection
