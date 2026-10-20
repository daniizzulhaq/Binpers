@extends('layouts.app')

@section('title', 'Rekomendasi Apresiasi')
@section('page-title', 'Rekomendasi Apresiasi')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted small mb-0">Anggota direkomendasikan bila total poin mencapai syarat dan apresiasi belum pernah diberikan kepadanya. Nilai poin dan syarat adalah contoh yang dapat dikonfigurasi.</p>
        <a href="{{ route('hrd.rewards.create') }}" class="btn btn-primary text-nowrap ms-3"><i class="bi bi-plus-lg me-1"></i>Tambah</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Apresiasi</th><th class="text-end">Syarat poin</th><th class="text-end">Memenuhi syarat</th><th class="text-end">Sudah diberikan</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rewards as $r)
                        <tr>
                            <td><div class="fw-semibold">{{ $r->name }}</div><div class="small text-muted">{{ $r->description }}</div></td>
                            <td class="text-end">{{ $r->min_points }}</td>
                            <td class="text-end"><span class="badge text-bg-warning">{{ $r->eligible_count }}</span></td>
                            <td class="text-end">{{ $r->granted_count }}</td>
                            <td>{!! $r->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('hrd.rewards.recommendations', $r) }}" class="btn btn-sm btn-primary">Rekomendasi</a>
                                <a href="{{ route('hrd.rewards.edit', $r) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('hrd.rewards.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Hapus apresiasi ini?')">
                                    @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="award">Belum ada apresiasi.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
