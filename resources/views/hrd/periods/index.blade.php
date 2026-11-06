@extends('layouts.app')

@section('title', 'Periode Penilaian')
@section('page-title', 'Periode & Kriteria')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetAllModal">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Semua Penilaian
        </button>
        <a href="{{ route('hrd.periods.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Periode</a>
    </div>

    <div class="modal fade" id="resetAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('hrd.periods.reset-all') }}" class="modal-content"
                  onsubmit="return confirm('Yakin menghapus SEMUA data penilaian? Tindakan ini tidak dapat dibatalkan.')">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5 text-danger"><i class="bi bi-exclamation-octagon me-1"></i>Reset Semua Penilaian</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-2">Data berikut di <strong>semua periode</strong> akan dihapus permanen:</p>
                    <ul class="small">
                        <li>{{ $resetCounts['assessments'] }} rekap penilaian (beserta detail, bukti, riwayat persetujuan)</li>
                        <li>{{ $resetCounts['notes'] }} penilaian harian (Apresiasi/Teguran) beserta fotonya</li>
                        <li>{{ $resetCounts['points'] }} transaksi poin perolehan dari rekap</li>
                        <li>{{ $resetCounts['monthly'] }} rekap bulanan terkunci</li>
                        <li>Notifikasi terkait penilaian</li>
                    </ul>
                    <p class="small text-muted">Tetap ada: {{ $resetCounts['periods'] }} periode beserta kriteria &amp; bobotnya (semua kembali ke Draft),
                        data personel, Riwayat Pelanggaran, penyesuaian poin manual, dan pemberian apresiasi.</p>
                    <label class="form-label small" for="reset_all_confirmation">Ketik <code>RESET SEMUA</code> untuk konfirmasi</label>
                    <input type="text" id="reset_all_confirmation" name="confirmation" class="form-control" autocomplete="off" required pattern="RESET SEMUA">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset Semua</button>
                </div>
            </form>
        </div>
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
