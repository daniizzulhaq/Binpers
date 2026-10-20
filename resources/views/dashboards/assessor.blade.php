@extends('layouts.mobile')

@section('title', 'Beranda')

@section('content')
    @include('partials.bp-hero', ['person' => $ah['me'] ?? null])

    @if (! $ah)
        <div class="alert alert-info d-flex gap-2 align-items-start">
            <x-icon name="info" :size="20" class="mt-1" />
            <span>Data personel Anda belum dibuat. Hubungi HRD agar anggota binaan dapat ditetapkan.</span>
        </div>
    @else
        @php
            $s = $ah['status'];
            $period = $ah['period'];
            $binaan = $ah['binaan'];
            $draft = (int) ($s['draft'] ?? 0);
            $submitted = (int) ($s['submitted'] ?? 0);
            $revision = (int) ($s['revision'] ?? 0);
            $done = (int) ($s['approved'] ?? 0) + (int) ($s['finalized'] ?? 0);
            $started = $draft + $submitted + $revision + $done;
            $belum = max(0, $binaan - $started);
            $progress = $binaan > 0 ? min(100, (int) floor(($submitted + $done) / $binaan * 100)) : 0;
        @endphp

        {{-- Periode berjalan --}}
        <div class="bp-card p-3 mb-3">
            <div class="bp-title">Periode Penilaian</div>
            @if ($period)
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="fw-semibold text-break">{{ $period->name }}</div>
                        <div class="small text-muted">
                            <x-icon name="calendar" :size="13" class="me-1" />{{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}
                        </div>
                    </div>
                    <span class="badge text-bg-success">Dibuka</span>
                </div>
                <div class="d-flex justify-content-between small text-muted mt-3 mb-1">
                    <span>Terkirim / selesai</span>
                    <span>{{ $submitted + $done }} dari {{ $binaan }} anggota</span>
                </div>
                <div class="progress" style="height:8px;" role="progressbar"
                     aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: {{ $progress }}%;background:var(--bp-navy);"></div>
                </div>
            @else
                <div class="text-muted small d-flex gap-2 align-items-start">
                    <x-icon name="hourglass" :size="18" class="mt-1" />
                    <span>Belum ada periode penilaian yang dibuka. Catatan kinerja tetap dapat dibuat kapan saja.</span>
                </div>
            @endif
        </div>

        {{-- Aksi cepat --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <a href="{{ route('assessor.assessments.index') }}" class="bp-action bp-action-primary h-100">
                    <x-icon name="clipboard-check" :size="26" /><span>Menilai Anggota</span>
                </a>
            </div>
            <div class="col-6">
                <a href="{{ route('notes.create') }}" class="bp-action bp-action-gold h-100">
                    <x-icon name="pencil" :size="26" /><span>Tulis Catatan</span>
                </a>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="bp-card mb-3 p-2">
            <div class="row g-0">
                @foreach ([
                    [$binaan, 'Anggota Binaan'],
                    [$belum, 'Belum Dinilai'],
                    [$draft, 'Draft'],
                    [$submitted, 'Menunggu Periksa'],
                    [$done, 'Disetujui / Final'],
                    [$revision, 'Perlu Revisi'],
                ] as [$n, $l])
                    <div class="col-4 bp-stat">
                        <div class="n {{ $l === 'Perlu Revisi' && $n > 0 ? 'text-danger' : '' }}">{{ $n }}</div>
                        <div class="l">{{ $l }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Perlu revisi --}}
        @if ($ah['revisions']->isNotEmpty())
            <div class="bp-card mb-3 p-3">
                <div class="bp-title text-danger"><x-icon name="alert" :size="16" :stroke="2" />Perlu Revisi</div>
                <div class="list-group list-group-flush">
                    @foreach ($ah['revisions'] as $r)
                        <a href="{{ route('assessor.assessments.form', [$r->assessment_period_id, $r->assessee_id]) }}"
                           class="list-group-item list-group-item-action px-0 d-flex align-items-center gap-2">
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-break">{{ $r->assessee->user->name }}</div>
                                <div class="small text-muted">{{ $r->period->name }}</div>
                            </div>
                            <x-icon name="chevron" :size="18" class="text-muted" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Catatan yang saya buat --}}
        <div class="bp-card mb-3 p-3">
            <div class="d-flex align-items-center">
                <div class="bp-title mb-0">Catatan yang Saya Buat</div>
                <a href="{{ route('notes.index') }}" class="small ms-auto">Lihat semua</a>
            </div>
            <div class="small text-muted mt-1 mb-2">{{ $ah['notesMonth'] }} catatan bulan ini</div>

            @forelse ($ah['recentGiven'] as $n)
                <div class="d-flex align-items-start gap-2 py-2 border-top">
                    <span class="badge text-bg-{{ $n->type->color() }} mt-1">
                        <x-icon :name="$n->type->svg()" :size="13" :stroke="2.2" />
                    </span>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-semibold text-break">{{ $n->title }}</div>
                        <div class="small text-muted text-break">
                            {{ $n->subject->user->name }} · {{ $n->incident_date->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted small border-top pt-2">Belum ada catatan yang Anda buat.</div>
            @endforelse
        </div>

        {{-- Catatan untuk saya --}}
        @include('partials.my-recent-notes')
    @endif
@endsection
