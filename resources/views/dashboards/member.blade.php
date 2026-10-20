@extends('layouts.mobile')

@section('title', 'Beranda')

@section('content')
    @php
        $gradeColor = fn (?string $g) => match ($g) {
            'Sangat Baik' => 'success',
            'Baik' => 'primary',
            'Cukup' => 'warning',
            default => 'danger',
        };
    @endphp

    @include('partials.bp-hero', ['person' => $home['me'] ?? null])

    @if (! $home)
        <div class="alert alert-info d-flex gap-2 align-items-start">
            <x-icon name="info" :size="20" class="mt-1" />
            <span>Data personel Anda belum dibuat. Hubungi HRD agar hasil penilaian dan catatan dapat ditampilkan.</span>
        </div>
    @else
        @php
            $results = $home['results'];
            $latest = $results->first();
            $points = $home['points'];
            $next = $home['nextReward'];
            $positive = (int) ($home['noteCounts']['positive'] ?? 0);
            $negative = (int) ($home['noteCounts']['negative'] ?? 0);
            $chart = $results->reverse()->values();
        @endphp

        {{-- Nilai terbaru --}}
        <div class="bp-card p-3 p-md-4 mb-3">
            <div class="d-flex align-items-center mb-2">
                <div class="bp-title mb-0">Penilaian Terbaru</div>
                @if ($latest)
                    <a href="{{ route('member.results.show', $latest->id) }}" class="small ms-auto">Rincian</a>
                @endif
            </div>

            @if ($latest)
                @php
                    $c = $gradeColor($latest->grade);
                    $score = min(100, max(0, (float) $latest->final_score));
                @endphp
                <div class="d-flex align-items-end gap-3">
                    <div class="display-5 fw-bold lh-1 text-{{ $c }}">{{ number_format((float) $latest->final_score, 2) }}</div>
                    <div class="pb-1">
                        <span class="badge text-bg-{{ $c }}">{{ $latest->grade }}</span>
                        <div class="small text-muted mt-1">{{ $latest->period->name }}</div>
                    </div>
                </div>
                <div class="progress mt-3" style="height:8px;" role="progressbar"
                     aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-{{ $c }}" style="width: {{ $score }}%"></div>
                </div>
            @else
                <div class="text-center text-muted py-3">
                    <x-icon name="hourglass" :size="34" :stroke="1.4" class="mb-1" />
                    <div class="small">Belum ada hasil penilaian yang final.</div>
                </div>
            @endif
        </div>

                {{-- Statistik ringkas --}}
        @php
            $violation = (int) ($home['noteCounts']['violation'] ?? 0);
        @endphp
        <div class="row g-2 mb-3">
            <div class="col-3">
                <div class="bp-card bp-stat h-100">
                    <x-icon name="points" :size="24" class="d-block mx-auto mb-1" style="color:var(--bp-navy);" />
                    <div class="n">{{ number_format($points) }}</div>
                    <div class="l">Poin</div>
                </div>
            </div>
            <div class="col-3">
                <a href="{{ route('notes.mine', ['type' => 'positive']) }}" class="text-decoration-none d-block h-100">
                    <div class="bp-card bp-stat h-100">
                        <x-icon name="thumbs-up" :size="24" class="d-block mx-auto mb-1 text-success" />
                        <div class="n text-success">{{ $positive }}</div>
                        <div class="l">Apresiasi</div>
                    </div>
                </a>
            </div>
            <div class="col-3">
                <a href="{{ route('notes.mine', ['type' => 'negative']) }}" class="text-decoration-none d-block h-100">
                    <div class="bp-card bp-stat h-100">
                        <x-icon name="alert" :size="24" class="d-block mx-auto mb-1" style="color:#d97706;" />
                        <div class="n" style="color:#b45309;">{{ $negative }}</div>
                        <div class="l">Teguran</div>
                    </div>
                </a>
            </div>
            <div class="col-3">
                <a href="{{ route('notes.mine', ['type' => 'violation']) }}" class="text-decoration-none d-block h-100">
                    <div class="bp-card bp-stat h-100">
                        <x-icon name="file-alert" :size="24" class="d-block mx-auto mb-1 text-danger" />
                        <div class="n text-danger">{{ $violation }}</div>
                        <div class="l">Pelanggaran</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Progres apresiasi berikutnya --}}
        @if ($next)
            @php
                $pct = min(100, (int) floor($points / max(1, $next->min_points) * 100));
            @endphp
            <div class="bp-card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <span class="fw-semibold text-break d-flex align-items-center gap-2">
                        <x-icon name="trophy" :size="20" style="color:var(--bp-gold);" />{{ $next->name }}
                    </span>
                    <span class="small text-muted text-nowrap">{{ number_format($points) }} / {{ number_format($next->min_points) }}</span>
                </div>
                <div class="progress" style="height:8px;" role="progressbar"
                     aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: {{ $pct }}%;background:var(--bp-gold);"></div>
                </div>
                <div class="small text-muted mt-1">
                    Kurang {{ number_format($next->min_points - $points) }} poin lagi.
                </div>
            </div>
        @endif

        {{-- Tren nilai --}}
        @if ($chart->count() > 1)
            <div class="bp-card p-3 mb-3">
                <div class="bp-title">Tren Nilai</div>
                <div class="d-flex align-items-end justify-content-around gap-2" style="height:110px;">
                    @foreach ($chart as $r)
                        @php
                            $barH = max(8, min(100, (float) $r->final_score)) * 0.7;
                        @endphp
                        <div class="flex-fill text-center">
                            <div class="small fw-semibold">{{ number_format((float) $r->final_score, 0) }}</div>
                            <div class="rounded-top bg-{{ $gradeColor($r->grade) }}" style="height: {{ $barH }}px;"></div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-around gap-2 border-top pt-1">
                    @foreach ($chart as $r)
                        <div class="flex-fill text-center small text-muted text-truncate" title="{{ $r->period->name }}">
                            {{ $r->period->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Menu cepat --}}
        <div class="row g-2 mb-3">
            @foreach ([
                ['member.results.index', 'chart', 'Hasil Penilaian', null],
                ['notes.mine', 'notebook', 'Catatan Kinerja', null],
                ['notifications.index', 'bell', 'Notifikasi', $home['unread']],
                ['profile.show', 'id-card', 'Profil Saya', null],
            ] as [$route, $icon, $label, $badge])
                <div class="col-6">
                    <a href="{{ route($route) }}" class="bp-card text-decoration-none d-block h-100">
                        <div class="p-3 d-flex align-items-center gap-2 position-relative">
                            <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width:42px;height:42px;background:rgba(11,42,91,.08);color:var(--bp-navy);">
                                <x-icon :name="$icon" :size="22" />
                            </span>
                            <span class="small fw-semibold text-body lh-sm">{{ $label }}</span>
                            @if ($badge)
                                <span class="badge rounded-pill text-bg-danger position-absolute top-0 end-0 m-2">{{ $badge > 99 ? '99+' : $badge }}</span>
                            @endif
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Catatan terbaru --}}
        @include('partials.my-recent-notes')
    @endif
@endsection
