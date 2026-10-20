@extends('layouts.app')

@section('title', 'Struktur Organisasi')
@section('page-title', 'Struktur Organisasi')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode atau nama">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('hrd.units.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Unit</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Kode</th><th>Nama</th><th>Jenis</th><th>Induk</th><th class="text-end">Sub-unit</th><th class="text-end">Penempatan</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($units as $unit)
                        <tr>
                            <td><code>{{ $unit->code }}</code></td>
                            <td>{{ $unit->name }}</td>
                            <td><span class="badge text-bg-light border">{{ $unit->type->label() }}</span></td>
                            <td>{{ $unit->parent?->name ?? '—' }}</td>
                            <td class="text-end">{{ $unit->children_count }}</td>
                            <td class="text-end">{{ $unit->assignments_count }}</td>
                            <td>{!! $unit->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('hrd.units.edit', $unit) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('hrd.units.destroy', $unit) }}" class="d-inline" onsubmit="return confirm('Hapus unit ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty icon="diagram-3">Belum ada unit.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $units->links() }}</div>
@endsection
