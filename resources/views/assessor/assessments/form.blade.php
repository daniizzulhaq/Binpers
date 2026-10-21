@extends(auth()->user()->layout())

@section('title', 'Rekap Penilaian')
@section('page-title', 'Rekap Penilaian')

@push('styles')
    <style>
        .crit-score { font-size: 1.35rem; font-weight: 700; line-height: 1; }
        .action-bar {
            background: #fff; border-top: 1px solid #dfe3ea;
            box-shadow: 0 -4px 14px rgba(7, 27, 61, .1); padding: .65rem .9rem; z-index: 1025;
        }
        .action-bar.is-mobile {
            position: fixed; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: var(--bp-width, 450px);
            bottom: calc(62px + env(safe-area-inset-bottom));
        }
        .action-bar.is-desktop { position: sticky; bottom: 0; border-radius: .75rem .75rem 0 0; }
        .action-bar .btns { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    </style>
@endpush

@section('content')
    @php
        $d = auth()->user()->usesDesktopLayout();
        $cur = $assessee->currentAssignment;
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
        $signed = fn (int $p) => ($p > 0 ? '+' : '').$p;
        // Kapolres: tidak ada atasan di sistem, rekap langsung final saat dikirim.
        $autoFinal = auth()->user()->hasRole(\App\Enums\RoleName::Kapolres->value);
        $submitConfirm = $autoFinal
            ? 'Kirim rekap ini? Rekap Kapolres LANGSUNG FINAL: nilai dikunci, tidak dapat diubah atau direvisi, dan hasilnya dapat dilihat anggota.'
            : 'Kirim rekap ini untuk disetujui? Setelah dikirim, penilaian harian anggota ini pada bulan tersebut dikunci kecuali rekap dikembalikan untuk revisi.';
        $byCriterion = $notes->groupBy(fn ($n) => $n->assessment_criterion_id ?? 0);
    @endphp

    <a href="{{ route('assessor.assessments.index') }}" class="btn btn-link px-0 mb-2">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>

    {{-- Anggota & nilai sementara --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="small text-muted">Rekap · {{ $period->name }}</div>
                    <div class="fw-bold fs-5 text-break">{{ $assessee->user->name }}</div>
                    <div class="small text-muted">{{ $assessee->rank ?: '-' }} · NRP {{ $assessee->nrp }}</div>
                    <div class="small text-muted text-break">{{ $cur?->position?->name ?? '-' }} · {{ $cur?->unit?->name ?? '-' }}</div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fs-3 fw-bold text-primary lh-1">{{ $fmt($summary['final']) }}</div>
                    <div class="small text-muted">{{ $summary['grade'] }}</div>
                </div>
            </div>

            <a href="{{ route('notes.create', ['subject_id' => $assessee->id]) }}" class="btn btn-primary w-100 mt-3">
                <i class="bi bi-plus-lg me-1"></i>Tambah Penilaian Harian
            </a>
        </div>
    </div>

    @if ($autoFinal)
        <div class="alert alert-warning small">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Rekap langsung final</div>
            Sebagai Kapolres, rekap Anda tidak memerlukan persetujuan dan langsung final saat dikirim.
        </div>
    @endif

    @if ($revisionNotes)
        <div class="alert alert-danger small">
            <div class="fw-semibold mb-1"><i class="bi bi-arrow-counterclockwise me-1"></i>Dikembalikan untuk revisi</div>
            {{ $revisionNotes }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger small">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-light border small">
        Nilai dihitung otomatis: setiap kriteria mulai dari <strong>{{ $fmt($baseScore) }}</strong>,
        ditambah poin apresiasi dan dikurangi poin teguran pada bulan ini (0–100),
        lalu dirata-rata sesuai bobot.
    </div>

    {{-- Nilai per kriteria beserta penilaian hariannya --}}
    @foreach ($criteria as $i => $c)
        @php
            $b = $summary['criteria'][$c->id];
            $items = $byCriterion->get($c->id, collect());
        @endphp

        <div class="card border-0 shadow-sm mb-2">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="fw-semibold text-break">{{ $i + 1 }}. {{ $c->name }}</div>
                        <div class="small text-muted">
                            Bobot {{ $fmt($c->weight) }}% ·
                            {{ $b['positive'] }} apresiasi · {{ $b['negative'] }} teguran
                            @if ($b['points'] !== 0) ({{ $signed($b['points']) }}) @endif
                        </div>
                    </div>
                    <div class="crit-score {{ $b['points'] > 0 ? 'text-success' : ($b['points'] < 0 ? 'text-danger' : 'text-secondary') }}">
                        {{ $fmt($b['score']) }}
                    </div>
                </div>

                @include('assessor.assessments._daily-notes', ['items' => $items])
            </div>
        </div>
    @endforeach

    {{-- Catatan tanpa kriteria (data lama) --}}
    @if ($byCriterion->has(0))
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="fw-semibold">Umum</div>
                        <div class="small text-muted">Langsung menambah/mengurangi nilai akhir.</div>
                    </div>
                    <div class="crit-score">{{ $signed($summary['general']['points']) }}</div>
                </div>

                @include('assessor.assessments._daily-notes', ['items' => $byCriterion->get(0)])
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('assessor.assessments.save', [$period, $assessee]) }}" class="mt-3">
        @csrf

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <label class="form-label fw-semibold" for="member_notes">Catatan untuk anggota <span class="text-muted fw-normal">(opsional)</span></label>
                <textarea id="member_notes" name="member_notes" rows="3" maxlength="1000" class="form-control"
                          placeholder="Ditampilkan kepada anggota setelah rekap final.">{{ old('member_notes', $assessment?->member_notes) }}</textarea>

                <label class="form-label fw-semibold mt-3" for="general_notes">Catatan internal <span class="text-muted fw-normal">(opsional)</span></label>
                <textarea id="general_notes" name="general_notes" rows="3" maxlength="2000" class="form-control"
                          placeholder="Hanya terlihat oleh penilai dan pemeriksa.">{{ old('general_notes', $assessment?->general_notes) }}</textarea>
            </div>
        </div>

        @if (! $d)
            <div style="height:130px;"></div>
        @endif

        <div class="action-bar {{ $d ? 'is-desktop' : 'is-mobile' }}">
            <div class="small mb-2">
                Nilai bulan ini: <strong>{{ $fmt($summary['final']) }}</strong> · {{ $summary['grade'] }}
                · {{ $summary['count'] }} penilaian harian
            </div>
            <div class="btns">
                <button type="submit" name="action" value="draft" class="btn btn-outline-primary">
                    <i class="bi bi-save me-1"></i>Simpan Catatan
                </button>
                <button type="submit" name="action" value="submit" class="btn btn-primary"
                        onclick="return confirm(@js($submitConfirm))">
                    <i class="bi bi-send me-1"></i>{{ $autoFinal ? 'Kirim & Final' : 'Kirim Rekap' }}
                </button>
            </div>
        </div>
    </form>
@endsection
