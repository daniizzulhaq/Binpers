@extends('layouts.app')

@section('title', 'Rekomendasi '.$reward->name)
@section('page-title', 'Rekomendasi: '.$reward->name)

@section('content')
    <div class="mb-3">
        <a href="{{ route('hrd.rewards.index') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali</a>
        <span class="text-muted small ms-3">Syarat {{ $reward->min_points }} poin · {{ $reward->is_active ? 'Aktif' : 'Nonaktif' }}</span>
    </div>

    <form method="GET" class="d-flex gap-2 mb-3" style="max-width: 420px">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari NRP atau nama">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Memenuhi syarat ({{ $eligible->total() }})</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Anggota</th><th>Jabatan / Unit</th><th class="text-end">Total poin</th><th style="min-width:260px">Pemberian</th></tr></thead>
                <tbody>
                    @forelse ($eligible as $p)
                        <tr>
                            <td><div class="fw-semibold">{{ $p->user->name }}</div><div class="small text-muted">{{ $p->nrp }}</div></td>
                            <td class="small">{{ $p->currentAssignment?->position?->name ?? '—' }}<br>{{ $p->currentAssignment?->unit?->name ?? '' }}</td>
                            <td class="text-end fw-bold">{{ (int) $p->total_points }}</td>
                            <td>
                                @if ($reward->is_active)
                                    <form method="POST" action="{{ route('hrd.rewards.grant', [$reward, $p]) }}" class="d-flex gap-2"
                                          onsubmit="return confirm('Catat pemberian apresiasi untuk {{ $p->user->name }}?')">
                                        @csrf
                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Catatan (opsional)">
                                        <button class="btn btn-sm btn-success text-nowrap">Berikan</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Apresiasi nonaktif</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty icon="people">Belum ada anggota yang memenuhi syarat.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mb-4">{{ $eligible->links() }}</div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Riwayat pemberian (20 terakhir)</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light"><tr><th>Tanggal</th><th>Anggota</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @forelse ($granted as $g)
                        <tr><td>{{ $g->created_at->format('d/m/Y H:i') }}</td><td>{{ $g->personnel->user->name }}</td><td class="small text-muted">{{ $g->description }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Belum pernah diberikan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
