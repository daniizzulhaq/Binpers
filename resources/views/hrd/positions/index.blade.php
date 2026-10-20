@extends('layouts.app')

@section('title', 'Jabatan')
@section('page-title', 'Jabatan')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari jabatan">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('hrd.positions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Jabatan</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Nama</th><th>Deskripsi</th><th class="text-end">Dipakai</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($positions as $position)
                        <tr>
                            <td class="fw-semibold">{{ $position->name }}</td>
                            <td class="text-muted">{{ $position->description ?? '—' }}</td>
                            <td class="text-end">{{ $position->assignments_count }}</td>
                            <td>{!! $position->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('hrd.positions.edit', $position) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('hrd.positions.destroy', $position) }}" class="d-inline" onsubmit="return confirm('Hapus jabatan ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="briefcase">Belum ada jabatan.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $positions->links() }}</div>
@endsection
