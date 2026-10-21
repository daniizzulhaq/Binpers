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
            <span>Data personel Anda belum dibuat. Hubungi HRD agar nilai dan catatan dapat ditampilkan.</span>
        </div>
    @else
        @php
            $current = $home['current'];
            $score = $home['score'];
            $positive = (int) ($home['noteCounts']['positive'] ?? 0);
            $negative = (int) ($home['noteCounts']['negative'] ?? 0);
            $violation = (int) ($home['noteCounts']['violation'] ?? 0);
            $chart = $home['history']->reverse()->values();
        @endphp

        {{-- Nilai bulan ini --}}
        <div class="bp-card p-3 p-md-4 mb-3">
            <div class="d-flex align-items-center mb-1">
                <div class="bp-title mb-0">Nilai Bulan Ini</div>
                @if ($score)
                    <span class="badge ms-auto {{ $score['locked'] ? 'text-bg-dark' : 'text-bg-light border' }}">
                        {{ $score['locked'] ? 'Final' : 'Sementara' }}
                    </span>
                @endif
            </div>

            @if (! $current)
                <div class="text-center text-muted py-3 small">Belum ada periode penilaian.</div>
            @else
                <div class="small text-muted mb-2">{{ $current->name }}</div>

                @if ($score['total'] === null)
                    <div class="text-center text-muted py-2">
                        <x-icon name="hourglass" :size="32" :stroke="1.4" class="mb-1" />
                        <div class="small">Belum dinilai bulan ini.</div>
                    </div>
                @else
                    @php
                        $c = $gradeColor($score['grade']);
                    @endphp
                    <div class="d-flex align-items-end gap-3">
                        <div class="display-5 fw-bold lh-1 text-{{ $c }}">{{ number_format($score['total'], 2) }}</div>
                        <div class="pb-1">
                            <span class="badge text-bg-{{ $c }}">{{ $score['grade'] }}</span>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height:8px;" role="progressbar"
                         aria-valuenow="{{ $score['total'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-{{ $c }}" style="width: {{ $score['total'] }}%"></div>
                    </div>
                @endif

                <div class="row g-0 border-top mt-3 pt-2 text-center small">
                    <div class="col-4">
                        <div class="text-muted">Penilaian</div>
                        <div class="fw-semibold">{{ $score['base'] !== null ? number_format($score['base'], 2) : '–' }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted">Apresiasi</div>
                        <div class="fw-semibold text-success">+{{ $score['bonus'] }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted">Teguran</div>
                        <div class="fw-semibold" style="color:var(--bs-danger);">−{{ $score['penalty'] }}</div>
                    </div>
                </div>
                @if ($score['assessments'] > 1)
                    <div class="small text-muted text-center mt-1">Rata-rata dari {{ $score['assessments'] }} penilaian</div>
                @endif
            @endif
        </div>

        {{-- Catatan bulan ini --}}
        <div class="row g-2 mb-3">
            <div class="col-4">
                <a href="{{ route('notes.mine', ['type' => 'positive']) }}" class="text-decoration-none d-block h-100">
                    <div class="bp-card bp-stat h-100">
                        <x-icon name="thumbs-up" :size="24" class="d-block mx-auto mb-1 text-success" />
                        <div class="n text-success">{{ $positive }}</div>
                        <div class="l">Apresiasi</div>
                    </div>
                </a>
            </div>
            <div class="col-4">
                <a href="{{ route('notes.mine', ['type' => 'negative']) }}" class="text-decoration-none d-block h-100">
                    <div class="bp-card bp-stat h-100">
                        <x-icon name="alert" :size="24" class="d-block mx-auto mb-1" style="color:var(--bs-danger);" />
                        <div class="n" style="color:var(--bs-danger);">{{ $negative }}</div>
                        <div class="l">Teguran</div>
                    </div>
                </a>
            </div>
            <div class="col-4">
                <a href="{{ route('notes.mine', ['type' => 'violation']) }}" class="text-decoration-none d-block h-100">
                    <div class="bp-card bp-stat h-100">
                        <x-icon name="file-alert" :size="24" class="d-block mx-auto mb-1 text-dark" />
                        <div class="n text-dark">{{ $violation }}</div>
                        <div class="l">Pelanggaran</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Tren nilai bulanan --}}
        @if ($chart->count() > 1)
            <div class="bp-card p-3 mb-3">
                <div class="bp-title">Tren Nilai Bulanan</div>
                <div class="d-flex align-items-end justify-content-around gap-2" style="height:110px;">
                    @foreach ($chart as $h)
                        @php
                            $barH = $h['total'] !== null ? max(8, min(100, $h['total'])) * 0.7 : 6;
                        @endphp
                        <div class="flex-fill text-center">
                            <div class="small fw-semibold">{{ $h['total'] !== null ? number_format($h['total'], 0) : '–' }}</div>
                            <div class="rounded-top {{ $h['total'] !== null ? 'bg-'.$gradeColor($h['grade']) : 'bg-secondary-subtle' }}"
                                 style="height: {{ $barH }}px;"></div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-around gap-2 border-top pt-1">
                    @foreach ($chart as $h)
                        <div class="flex-fill text-center small text-muted text-truncate" title="{{ $h['period']->name }}">
                            {{ $h['period']->name }}
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

        @include('partials.my-recent-notes')
    @endif
@endsection
