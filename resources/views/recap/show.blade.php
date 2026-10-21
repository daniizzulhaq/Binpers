@extends('layouts.app')

@section('title', 'Detail Rekap')
@section('page-title', 'Detail Rekap')

@section('content')
    @php
        $gradeColor = fn (?string $g) => match ($g) {
            'Sangat Baik' => 'success',
            'Baik' => 'primary',
            'Cukup' => 'warning',
            default => 'danger',
        };
        $months = \App\Http\Controllers\MonthlyRecapController::MONTHS;
        $signed = fn (int $p) => ($p > 0 ? '+' : '').$p;
        $cur = $personnel->currentAssignment;
        $noteCounts = $notes->countBy(fn ($n) => $n->type->value);
    @endphp

    <a href="{{ route('recap.index', $backQuery) }}" class="btn btn-link px-0 mb-2">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke rekap
    </a>

    {{-- Personel & nilai --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="min-w-0">
                <div class="small text-muted">Rekap {{ $typeLabel }} · {{ $label }} ({{ $monthRange }})</div>
                <div class="fw-bold fs-5">{{ $personnel->user->name }}</div>
                <div class="small text-muted">{{ $personnel->rank ?: '-' }} · NRP {{ $personnel->nrp }}</div>
                <div class="small text-muted">{{ $cur?->position?->name ?? '-' }} · {{ $cur?->unit?->name ?? '-' }}</div>
            </div>
            <div class="text-end">
                <div class="fs-2 fw-bold text-primary lh-1">
                    {{ ($summary['total'] ?? null) !== null ? number_format($summary['total'], 2) : '–' }}
                </div>
                @if ($summary['grade'] ?? null)
                    <span class="badge text-bg-{{ $gradeColor($summary['grade']) }} mt-1">{{ $summary['grade'] }}</span>
                @else
                    <span class="badge text-bg-secondary mt-1">Belum dinilai</span>
                @endif
                <div class="small text-muted mt-1">
                    {{ $noteCounts['positive'] ?? 0 }} apresiasi · {{ $noteCounts['negative'] ?? 0 }} teguran
                    @if ($noteCounts['violation'] ?? 0) · {{ $noteCounts['violation'] }} pelanggaran @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Nilai per bulan --}}
    @if ($rangePeriods->count() > 1)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Nilai per bulan</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bulan</th>
                            <th class="text-end">Penilaian</th>
                            <th class="text-end">Apresiasi</th>
                            <th class="text-end">Teguran</th>
                            <th class="text-end">Total</th>
                            <th>Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rangePeriods as $rp)
                            @php $m = $monthly[$rp->id]; @endphp
                            <tr>
                                <td>
                                    {{ $rp->name }}
                                    @if ($rp->isRecapLocked()) <i class="bi bi-lock-fill small text-muted" title="Dikunci"></i> @endif
                                </td>
                                <td class="text-end">{{ $m['base'] !== null ? number_format($m['base'], 2) : '–' }}</td>
                                <td class="text-end text-success">+{{ $m['bonus'] }}</td>
                                <td class="text-end" style="color:var(--bs-danger);">−{{ $m['penalty'] }}</td>
                                <td class="text-end fw-bold">{{ $m['total'] !== null ? number_format($m['total'], 2) : '–' }}</td>
                                <td>
                                    @if ($m['grade'])
                                        <span class="badge text-bg-{{ $gradeColor($m['grade']) }}">{{ $m['grade'] }}</span>
                                    @else
                                        <span class="text-muted small">Belum dinilai</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Rekap dari setiap penilai --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">
            Rekap dari penilai <span class="text-muted fw-normal small">({{ $assessments->count() }})</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @if ($rangePeriods->count() > 1) <th>Bulan</th> @endif
                        <th>Penilai</th>
                        <th>Status</th>
                        <th class="text-end">Nilai</th>
                        <th>Catatan untuk anggota</th>
                        <th>Catatan internal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assessments as $a)
                        <tr>
                            @if ($rangePeriods->count() > 1)
                                <td class="text-nowrap small">{{ $months[$a->period->start_date->month] }} {{ $a->period->start_date->year }}</td>
                            @endif
                            <td>
                                <div class="fw-semibold">{{ $a->assessor?->user?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $a->assessor?->currentAssignment?->position?->name ?? '-' }}</div>
                            </td>
                            <td><span class="badge text-bg-{{ $a->status->color() }}">{{ $a->status->label() }}</span></td>
                            <td class="text-end fw-semibold">{{ $a->final_score !== null ? number_format((float) $a->final_score, 2) : '–' }}</td>
                            <td class="small text-break" style="min-width:12rem;">{{ $a->member_notes ?: '–' }}</td>
                            <td class="small text-break" style="min-width:12rem;">{{ $a->general_notes ?: '–' }}</td>
                            <td class="text-end">
                                <a href="{{ route('review.show', $a) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada rekap dari penilai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Semua catatan / penilaian harian --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">
            Penilaian harian &amp; catatan <span class="text-muted fw-normal small">({{ $notes->count() }})</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Penilai</th>
                        <th>Kriteria</th>
                        <th>Jenis</th>
                        <th class="text-end">Poin</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notes as $n)
                        <tr>
                            <td class="text-nowrap small">{{ $n->incident_date->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-semibold small">{{ $n->authorName() }}</div>
                                <div class="small text-muted">{{ $n->authorPosition() ?? '-' }}</div>
                            </td>
                            <td class="small">{{ $n->type === \App\Enums\NoteType::Violation ? '–' : $n->criterionName() }}</td>
                            <td>
                                <span class="badge text-bg-{{ $n->type->color() }}">{{ $n->type->shortLabel() }}</span>
                                @if ($n->levelLabel()) <div class="small text-muted">{{ $n->levelLabel() }}</div> @endif
                                @if ($n->status !== \App\Enums\NoteStatus::Approved)
                                    <span class="badge text-bg-{{ $n->status->color() }}">{{ $n->status->label() }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold {{ $n->points > 0 ? 'text-success' : ($n->points < 0 ? 'text-danger' : '') }}">
                                {{ $signed((int) $n->points) }}
                            </td>
                            <td class="small text-break" style="min-width:16rem;">
                                <div class="fw-semibold">{{ $n->title }}</div>
                                @if ($n->note) <div class="text-muted">{{ $n->note }}</div> @endif
                                @if ($n->image_path)
                                    <a href="{{ route('notes.image', $n) }}" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-image me-1"></i>Lihat foto
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada penilaian harian atau catatan pada rentang ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
