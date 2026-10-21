@extends('layouts.app')

@section('title', 'Detail Penilaian')
@section('page-title', 'Detail Penilaian')

@section('content')
    <div class="mb-3"><a href="{{ route('review.index') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali ke daftar</a></div>

    @unless (auth()->user()->can('assessments.approve'))
        <div class="alert alert-secondary small"><i class="bi bi-eye me-1"></i>Mode pemantauan: Anda hanya dapat melihat penilaian ini.</div>
    @endunless

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <div class="small text-muted">Anggota dinilai</div>
                            <div class="fw-semibold fs-5">{{ $assessment->assessee->user->name }}</div>
                            <div class="small text-muted">{{ $assessment->assessee->nrp }} · {{ $assessment->assesseePosition?->name ?? '—' }} · {{ $assessment->assesseeUnit?->name ?? '—' }}</div>
                        </div>
                        <div class="text-md-end">
                            <x-status-badge :status="$assessment->status" class="fs-6" />
                            @if ($assessment->final_score !== null)
                                <div class="score-big mt-2">{{ number_format((float) $assessment->final_score, 2) }}</div>
                                <div class="small text-muted">{{ $assessment->grade }}</div>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row small">
                        <div class="col-md-4"><span class="text-muted">Periode:</span> {{ $assessment->period->name }}</div>
                        <div class="col-md-4"><span class="text-muted">Penilai:</span> {{ $assessment->assessor->user->name }}</div>
                        <div class="col-md-4"><span class="text-muted">Revisi:</span> {{ $assessment->revision_count }}×</div>
                        <div class="col-12 mt-1"><span class="text-muted">Tujuan persetujuan:</span> {{ $routing->targetLabel($assessment) }}</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Skor per kriteria</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Kriteria</th><th class="text-end">Bobot</th><th class="text-end">Skor</th><th>Catatan</th><th>Bukti</th></tr></thead>
                        <tbody>
                            @foreach ($assessment->details->sortBy(fn ($d) => $d->criterion->sort_order) as $d)
                                <tr>
                                    <td>{{ $d->criterion->name }}</td>
                                    <td class="text-end">{{ $d->criterion->weight }}</td>
                                    <td class="text-end fw-semibold">{{ $d->score }} <span class="text-muted small">/ {{ $d->criterion->max_score + 0 }}</span></td>
                                    <td class="small">{{ $d->note ?? '—' }}</td>
                                    <td>
                                        @if ($d->evidence_path)
                                            <a href="{{ route('evidence.show', $d) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-paperclip"></i> Buka</a>
                                        @else — @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @include('assessor.assessments._recap-notes', ['assessment' => $assessment])

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Catatan</div>
                <div class="card-body small">
                    <div class="mb-2"><span class="text-muted">Catatan internal:</span><br>{{ $assessment->general_notes ?: '—' }}</div>
                    <div><span class="text-muted">Catatan untuk anggota:</span><br>{{ $assessment->member_notes ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if ($assessment->isStructuralReview() && $assessment->status === \App\Enums\AssessmentStatus::Submitted)
                <div class="alert alert-warning small">
                    <div class="fw-semibold"><i class="bi bi-hourglass-split me-1"></i>Menunggu persetujuan {{ $routing->targetLabel($assessment) }}</div>
                    Penilaian ini disetujui oleh atasan struktural penilai. Halaman ini hanya untuk pemantauan.
                </div>
            @endif

            @canany(['approve', 'returnForRevision', 'finalize'], $assessment)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Tindakan</div>
                    <div class="card-body">
                        @can('approve', $assessment)
                            <form method="POST" action="{{ route('review.approve', $assessment) }}" class="mb-3"
                                  onsubmit="return confirm('Setujui penilaian ini? Nilai akan langsung final dan dikunci.')">
                                @csrf
                                <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Catatan persetujuan (opsional)"></textarea>
                                <button class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Setujui &amp; Finalisasi</button>
                                <div class="form-text">Penilaian langsung final setelah disetujui.</div>
                            </form>
                        @endcan

                        @can('finalize', $assessment)
                            <form method="POST" action="{{ route('review.finalize', $assessment) }}" class="mb-3"
                                  onsubmit="return confirm('Penilaian lama ini sudah disetujui. Finalisasi akan mengunci nilai. Lanjutkan?')">
                                @csrf
                                <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Catatan finalisasi (opsional)"></textarea>
                                <button class="btn btn-primary w-100"><i class="bi bi-patch-check me-1"></i>Finalisasi</button>
                            </form>
                        @endcan

                        @can('returnForRevision', $assessment)
                            <form method="POST" action="{{ route('review.return', $assessment) }}">
                                @csrf
                                <textarea name="notes" class="form-control mb-2 @error('notes') is-invalid @enderror" rows="3" placeholder="Alasan pengembalian (wajib)" required>{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                                <button class="btn btn-outline-danger w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan untuk revisi</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endcanany

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Riwayat proses</div>
                <div class="card-body">
                    <div class="timeline">
                        @forelse ($assessment->approvals as $ap)
                            <div class="timeline-item">
                                <div class="fw-semibold small">{{ $ap->action->label() }}</div>
                                <div class="small text-muted">{{ $ap->user->name }} · {{ $ap->created_at->format('d/m/Y H:i') }}</div>
                                @if ($ap->notes)<div class="small mt-1">{{ $ap->notes }}</div>@endif
                            </div>
                        @empty
                            <div class="text-muted small">Belum ada proses.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
