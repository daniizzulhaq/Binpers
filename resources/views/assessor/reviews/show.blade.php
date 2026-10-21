@extends('layouts.mobile')

@section('title', 'Persetujuan Penilaian')

@push('styles')
    <style>
        .indicator-box { background: #f4f6fa; border-left: 3px solid #c9a227; border-radius: .5rem; }
        .review-bar {
            position: fixed; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: var(--bp-width, 450px); z-index: 1025;
            bottom: calc(62px + env(safe-area-inset-bottom));
            background: #fff; border-top: 1px solid #dfe3ea;
            box-shadow: 0 -4px 14px rgba(7, 27, 61, .1); padding: .65rem .9rem;
        }
        .review-bar .btns { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
        .kv { display: flex; justify-content: space-between; gap: .75rem; padding: .3rem 0; font-size: .85rem; }
        .kv + .kv { border-top: 1px dashed #e3e7ee; }
        .kv span:first-child { color: #6b7685; flex-shrink: 0; }
        .kv span:last-child { text-align: right; word-break: break-word; }
    </style>
@endpush

@section('content')
    @php
        $a = $assessment;
        $rows = $a->details->sortBy(fn ($d) => $d->criterion->sort_order);
        $assessorJob = $a->assessor->currentAssignment;
        $hasActions = $canApprove || $canReturn || $canFinalize;
        $openReturn = $errors->has('notes');
    @endphp

    <a href="{{ route('assessor.reviews.index') }}" class="btn btn-link px-0 mb-2">
        <x-icon name="chevron" :size="16" style="transform: rotate(180deg);" class="me-1" />Kembali ke daftar
    </a>

    {{-- Ringkasan --}}
    <section class="bp-hero">
        <div class="bp-hero-star"><x-icon name="clipboard-check" :size="104" :stroke="1" /></div>
        <div class="bp-hero-greet">{{ $a->period->name }}</div>
        <div class="bp-hero-name">{{ $a->assessee->user->name }}</div>
        <div class="bp-hero-meta">{{ $a->assessee->rank ?: '-' }} · NRP {{ $a->assessee->nrp }}</div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="bp-chip">
                <x-icon name="org" :size="14" />
                <span class="text-truncate">{{ $a->assesseePosition?->name ?? '—' }} · {{ $a->assesseeUnit?->name ?? '—' }}</span>
            </span>
        </div>
        @if ($a->final_score !== null)
            <div class="mt-3 d-flex align-items-baseline gap-2">
                <span class="fs-2 fw-bold" style="color: var(--bp-gold);">{{ number_format((float) $a->final_score, 2) }}</span>
                <span class="small opacity-75">{{ $a->grade }}</span>
            </div>
        @endif
    </section>

    @if ($errors->any() && ! $openReturn)
        <div class="alert alert-danger small">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Status & penilai --}}
    <div class="bp-card p-3 mb-3">
        <div class="bp-title">Data Penilaian</div>
        <div class="kv"><span>Status</span><span><x-status-badge :status="$a->status" /></span></div>
        <div class="kv"><span>Penilai</span><span>{{ $a->assessor->user->name }}</span></div>
        <div class="kv"><span>Jabatan penilai</span><span>{{ $assessorJob?->position?->name ?? '—' }} · {{ $assessorJob?->unit?->name ?? '—' }}</span></div>
        <div class="kv"><span>Tujuan persetujuan</span><span>{{ $a->reviewerUnit?->name ?? '—' }}</span></div>
        <div class="kv"><span>Dikirim</span><span>{{ $a->submitted_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
        <div class="kv"><span>Revisi</span><span>{{ $a->revision_count }}×</span></div>
    </div>

    {{-- Skor per kriteria --}}
    <div class="bp-card p-3 mb-3">
        <div class="bp-title">Skor per Kriteria</div>
        @foreach ($rows as $i => $d)
            <div class="py-2 {{ $loop->first ? '' : 'border-top' }}">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="fw-semibold text-break">{{ $loop->iteration }}. {{ $d->criterion->name }}</div>
                    <div class="text-nowrap text-end">
                        <span class="fw-bold">{{ (float) $d->score }}</span>
                        <span class="small text-muted">/ {{ (float) $d->criterion->max_score }}</span>
                    </div>
                </div>
                <div class="small text-muted">Bobot {{ rtrim(rtrim(number_format((float) $d->criterion->weight, 2), '0'), '.') }}%</div>

                @if ($d->criterion->description)
                    <div class="indicator-box small p-2 mt-2">
                        <div class="fw-semibold mb-1">Indikator yang dinilai</div>
                        {{ $d->criterion->description }}
                    </div>
                @endif

                @if ($d->note)
                    <div class="small mt-2"><span class="text-muted">Catatan:</span> {{ $d->note }}</div>
                @endif

                @if ($d->evidence_path)
                    <a href="{{ route('evidence.show', $d) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mt-2">
                        <x-icon name="paperclip" :size="14" class="me-1" />Lihat bukti
                    </a>
                @endif
            </div>
        @endforeach
    </div>

    @include('assessor.assessments._recap-notes', ['assessment' => $a])

    {{-- Catatan umum --}}
    <div class="bp-card p-3 mb-3">
        <div class="bp-title">Catatan Penilai</div>
        <div class="small mb-2">
            <div class="text-muted">Catatan internal</div>
            {{ $a->general_notes ?: '—' }}
        </div>
        <div class="small">
            <div class="text-muted">Catatan untuk anggota</div>
            {{ $a->member_notes ?: '—' }}
        </div>
    </div>

    {{-- Riwayat status --}}
    <div class="bp-card p-3 mb-3">
        <div class="bp-title">Riwayat Status</div>
        @forelse ($a->approvals as $ap)
            <div class="py-2 {{ $loop->first ? '' : 'border-top' }}">
                <div class="d-flex justify-content-between gap-2">
                    <span class="fw-semibold small">{{ $ap->action->label() }}</span>
                    <span class="small text-muted text-nowrap">{{ $ap->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="small text-muted">oleh {{ $ap->user->name }}</div>
                @if ($ap->notes)
                    <div class="small mt-1">{{ $ap->notes }}</div>
                @endif
            </div>
        @empty
            <div class="text-muted small">Belum ada proses.</div>
        @endforelse
    </div>

    @if ($hasActions)
        <div style="height:90px;"></div>

        {{-- Bar aksi --}}
        <div class="review-bar">
            <div class="btns">
                @if ($canReturn)
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#returnModal">
                        <x-icon name="undo" :size="18" class="me-1" />Kembalikan
                    </button>
                @endif
                @if ($canFinalize)
                    <form method="POST" action="{{ route('assessor.reviews.finalize', $a) }}" @if (! $canReturn) style="grid-column: span 2;" @endif
                          onsubmit="return confirm('Finalisasi penilaian ini? Nilai akan dikunci dan hasilnya dapat dilihat anggota.')">
                        @csrf
                        <button class="btn btn-success w-100">
                            <x-icon name="check-circle" :size="18" class="me-1" />Finalisasi
                        </button>
                    </form>
                @endif
                @if ($canApprove)
                    <form method="POST" action="{{ route('assessor.reviews.approve', $a) }}" @if (! $canReturn) style="grid-column: span 2;" @endif
                          onsubmit="return confirm('Setujui penilaian ini? Nilai akan langsung final, dikunci, dan hasilnya dapat dilihat anggota.')">
                        @csrf
                        <button class="btn btn-success w-100">
                            <x-icon name="check-circle" :size="18" class="me-1" />Setujui
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if ($canReturn)
        <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('assessor.reviews.return', $a) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="returnModalLabel">Kembalikan untuk revisi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <label for="notes" class="form-label small">Catatan untuk penilai <span class="text-danger">*</span></label>
                        <textarea id="notes" name="notes" rows="4" maxlength="1000" required
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Jelaskan bagian yang perlu diperbaiki">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Wajib diisi, maksimal 1000 karakter.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-danger">Kembalikan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@if ($canReturn && $errors->has('notes'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('returnModal');
                if (el && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(el).show();
                }
            });
        </script>
    @endpush
@endif
