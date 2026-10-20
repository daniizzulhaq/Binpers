@extends('layouts.app')

@section('title', 'Personel')
@section('page-title', 'Data Personel')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <form method="GET" class="row g-2 flex-grow-1" style="max-width: 720px">
            <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari NRP, nama, atau username"></div>
            <div class="col-md-4">
                <select name="unit_id" class="form-select">
                    <option value="">Semua unit</option>
                    @foreach ($units as $u)
                        <option value="{{ $u->id }}" @selected((int) request('unit_id') === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button></div>
        </form>
        <a href="{{ route('hrd.personnel.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Tambah Personel</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>NRP</th><th>Nama</th><th>Pangkat</th><th>Jabatan</th><th>Unit</th><th>Role</th><th></th></tr></thead>
                <tbody>
                    @forelse ($personnel as $p)
                        <tr>
                            <td>{{ $p->nrp }}</td>
                            <td>
                                <div class="fw-semibold">{{ $p->user->name }}</div>
                                <div class="small text-muted">{{ $p->user->username }} @unless($p->user->is_active)<span class="badge text-bg-secondary">Nonaktif</span>@endunless</div>
                            </td>
                            <td>{{ $p->rank ?? '—' }}</td>
                            <td>{{ $p->currentAssignment?->position?->name ?? '—' }}</td>
                            <td>{{ $p->currentAssignment?->unit?->name ?? '—' }}</td>
                            <td><span class="badge text-bg-light border">{{ $p->user->roleLabel() }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('hrd.personnel.show', $p) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('hrd.personnel.edit', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="people">Belum ada personel.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $personnel->links() }}</div>
@endsection
