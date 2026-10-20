@extends('layouts.mobile')

@section('title', 'Hasil Penilaian')
@section('page-title', 'Hasil & Riwayat Penilaian')

@section('content')
    @php($base = request()->routeIs('assessor.*') ? 'assessor' : 'member')

    @if ($base === 'assessor')
        <a href="{{ route('assessor.home') }}" class="small d-inline-block mb-2"><i class="bi bi-arrow-left"></i> Beranda</a>
    @endif

    <p class="small text-muted">Hanya hasil yang sudah final yang ditampilkan.</p>

    @forelse ($assessments as $a)
        <a href="{{ route($base.'.results.show', $a->id) }}" class="card border-0 shadow-sm mb-2 list-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="lh-sm">
                        <div class="fw-semibold">{{ $a->period->name }}</div>
                        <div class="small text-muted">Final {{ $a->finalized_at?->format('d/m/Y') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $a->final_score, 2) }}</div>
                        <span class="badge text-bg-primary">{{ $a->grade }}</span>
                    </div>
                </div>
                @if ($a->member_notes)
                    <div class="small text-muted mt-2 border-top pt-2"><i class="bi bi-chat-left-text me-1"></i>{{ \Illuminate\Support\Str::limit($a->member_notes, 100) }}</div>
                @endif
            </div>
        </a>
    @empty
        <x-empty icon="bar-chart-line">Belum ada hasil penilaian yang final.</x-empty>
    @endforelse

    <div class="mt-3">{{ $assessments->links() }}</div>
@endsection
