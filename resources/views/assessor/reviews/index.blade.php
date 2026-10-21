@extends('layouts.mobile')

@section('title', 'Persetujuan Penilaian')

@section('content')
    <section class="bp-hero">
        <div class="bp-hero-star"><x-icon name="check-circle" :size="104" :stroke="1" /></div>
        <div class="bp-hero-greet">Persetujuan</div>
        <div class="bp-hero-name">Penilaian Kanit / Kasubag</div>
        <div class="bp-hero-meta">Periksa penilaian dari bawahan langsung Anda, lalu setujui atau kembalikan untuk revisi.</div>
    </section>

    @php
        $tabs = [
            'menunggu' => 'Menunggu',
            'diproses' => 'Sudah saya proses',
        ];
    @endphp

    <ul class="nav nav-pills nav-fill gap-1 mb-3">
        @foreach ($tabs as $key => $label)
            @php
                $count = $key === 'menunggu' ? ($tab === 'menunggu' ? $assessments->total() : $pendingCount) : null;
            @endphp
            <li class="nav-item">
                <a href="{{ route('assessor.reviews.index', ['tab' => $key]) }}"
                   class="nav-link py-2 {{ $tab === $key ? 'active' : 'bg-white border' }}">
                    {{ $label }}
                    @if ($count !== null)
                        <span class="badge {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }} ms-1">{{ $count }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    @forelse ($assessments as $a)
        <a href="{{ route('assessor.reviews.show', $a) }}" class="bp-card d-block p-3 mb-2 text-decoration-none text-body">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="fw-semibold text-break">{{ $a->assessee->user->name }}</div>
                    <div class="small text-muted">NRP {{ $a->assessee->nrp }} · {{ $a->assesseeUnit?->name ?? '—' }}</div>
                </div>
                <x-status-badge :status="$a->status" />
            </div>
            <div class="small text-muted mt-2 text-break">
                <x-icon name="user" :size="13" class="me-1" />Penilai {{ $a->assessor->user->name }}
                @if ($a->assessor->currentAssignment?->position)
                    ({{ $a->assessor->currentAssignment->position->name }})
                @endif
            </div>
            <div class="d-flex justify-content-between align-items-center small mt-1">
                <span class="text-muted">
                    <x-icon name="calendar" :size="13" class="me-1" />{{ $a->period->name }}
                    @if ($a->submitted_at)
                        · dikirim {{ $a->submitted_at->format('d/m/Y H:i') }}
                    @endif
                </span>
                @if ($a->final_score !== null)
                    <span>Nilai <strong>{{ number_format((float) $a->final_score, 2) }}</strong></span>
                @endif
            </div>
        </a>
    @empty
        <div class="bp-card p-4 text-center text-muted">
            <x-icon name="check-circle" :size="40" :stroke="1.4" class="d-block mx-auto mb-2" />
            @if ($tab === 'menunggu')
                Tidak ada penilaian yang menunggu persetujuan Anda.
            @else
                Belum ada penilaian yang Anda setujui atau kembalikan.
            @endif
        </div>
    @endforelse

    <div class="mt-3">{{ $assessments->links() }}</div>
@endsection
