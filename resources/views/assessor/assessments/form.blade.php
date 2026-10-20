@extends(auth()->user()->layout())

@section('title', 'Form Penilaian')
@section('page-title', 'Form Penilaian')

@push('styles')
    <style>
        .score-opt label { font-size: .68rem; line-height: 1.15; padding: .5rem .15rem; }
        .score-opt label .v { display: block; font-size: 1rem; font-weight: 700; }
        .indicator-box { background: #f4f6fa; border-left: 3px solid #c9a227; border-radius: .5rem; }
        details.more summary { cursor: pointer; list-style: none; }
        details.more summary::-webkit-details-marker { display: none; }
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
        $options = config('assessment.score_options');
        $cur = $assessee->currentAssignment;
    @endphp

    <a href="{{ route('assessor.assessments.index') }}" class="btn btn-link px-0 mb-2">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>

    {{-- Anggota yang dinilai --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="small text-muted">Menilai · {{ $period->name }}</div>
            <div class="fw-bold fs-5 text-break">{{ $assessee->user->name }}</div>
            <div class="small text-muted">{{ $assessee->rank ?: '-' }} · NRP {{ $assessee->nrp }}</div>
            <div class="small text-muted text-break">{{ $cur?->position?->name ?? '-' }} · {{ $cur?->unit?->name ?? '-' }}</div>
        </div>
    </div>

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

    {{-- Panduan skala --}}
    <details class="card border-0 shadow-sm mb-3 more">
        <summary class="card-body p-3 d-flex align-items-center">
            <span class="fw-semibold"><i class="bi bi-info-circle me-1"></i>Panduan skala penilaian</span>
            <i class="bi bi-chevron-down ms-auto"></i>
        </summary>
        <div class="px-3 pb-3">
            @foreach ($options as $o)
                <div class="d-flex gap-2 align-items-start mb-2">
                    <span class="badge text-bg-{{ $o['color'] }}" style="min-width:2.6rem;">{{ $o['value'] }}</span>
                    <div class="small"><strong>{{ $o['label'] }}</strong>: {{ $o['hint'] }}</div>
                </div>
            @endforeach
        </div>
    </details>

    <form method="POST" enctype="multipart/form-data" id="assessmentForm"
          action="{{ route('assessor.assessments.save', [$period, $assessee]) }}">
        @csrf

        @foreach ($criteria as $i => $c)
            @php
                $detail = $details->get($c->id);
                $oldScore = old('scores.'.$c->id, $detail?->score !== null ? (int) $detail->score : null);
                $hasMore = filled(old('notes.'.$c->id, $detail?->note)) || $detail?->evidence_path
                    || $errors->has('notes.'.$c->id) || $errors->has('evidence.'.$c->id);
            @endphp

            <div class="card border-0 shadow-sm mb-3 criterion"
                 data-weight="{{ (float) $c->weight }}" data-min="{{ (float) $c->min_score }}" data-max="{{ (float) $c->max_score }}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="fw-semibold text-break">{{ $i + 1 }}. {{ $c->name }}</div>
                        <span class="badge text-bg-light border text-nowrap">Bobot {{ rtrim(rtrim(number_format((float) $c->weight, 2), '0'), '.') }}%</span>
                    </div>

                    @if ($c->description)
                        <div class="indicator-box small p-2 mt-2">
                            <div class="fw-semibold mb-1">Indikator yang dinilai</div>
                            {{ $c->description }}
                        </div>
                    @endif

                    {{-- Nilai kosong bila belum dipilih (radio yang dipilih menimpa nilai ini) --}}
                    <input type="hidden" name="scores[{{ $c->id }}]" value="">

                    <div class="row g-1 row-cols-5 mt-2 score-opt">
                        @foreach ($options as $o)
                            <div class="col">
                                <input type="radio" class="btn-check" name="scores[{{ $c->id }}]"
                                       id="s-{{ $c->id }}-{{ $o['value'] }}" value="{{ $o['value'] }}"
                                       @checked($oldScore !== null && $oldScore !== '' && (int) $oldScore === (int) $o['value'])>
                                <label class="btn btn-outline-{{ $o['color'] }} w-100 h-100" for="s-{{ $c->id }}-{{ $o['value'] }}">
                                    <span class="v">{{ $o['value'] }}</span>{{ $o['label'] }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('scores.'.$c->id)
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    {{-- Catatan & bukti (opsional) --}}
                    <details class="more mt-2" @if ($hasMore) open @endif>
                        <summary class="small text-primary">
                            <i class="bi bi-plus-circle me-1"></i>Catatan / bukti (opsional)
                        </summary>

                        <div class="mt-2">
                            <textarea name="notes[{{ $c->id }}]" rows="2" maxlength="1000"
                                      class="form-control form-control-sm @error('notes.'.$c->id) is-invalid @enderror"
                                      placeholder="Catatan untuk kriteria ini">{{ old('notes.'.$c->id, $detail?->note) }}</textarea>
                        </div>

                        <div class="mt-2">
                            @if ($detail?->evidence_path)
                                <div class="d-flex align-items-center gap-2 small mb-1">
                                    <a href="{{ route('evidence.show', $detail) }}" target="_blank" rel="noopener">
                                        <i class="bi bi-paperclip me-1"></i>Lihat bukti
                                    </a>
                                    <div class="form-check ms-auto mb-0">
                                        <input class="form-check-input" type="checkbox" value="1"
                                               name="remove_evidence[{{ $c->id }}]" id="rm-{{ $c->id }}">
                                        <label class="form-check-label" for="rm-{{ $c->id }}">Hapus</label>
                                    </div>
                                </div>
                            @endif
                            <input type="file" name="evidence[{{ $c->id }}]" accept=".jpg,.jpeg,.png,.pdf"
                                   class="form-control form-control-sm @error('evidence.'.$c->id) is-invalid @enderror">
                            <div class="form-text">JPG, PNG, atau PDF, maks {{ round(config('assessment.evidence_max_kb') / 1024) }} MB.</div>
                        </div>
                    </details>
                </div>
            </div>
        @endforeach

        {{-- Catatan umum --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <label class="form-label fw-semibold" for="member_notes">Catatan untuk anggota <span class="text-muted fw-normal">(opsional)</span></label>
                <textarea id="member_notes" name="member_notes" rows="3" maxlength="1000" class="form-control"
                          placeholder="Ditampilkan kepada anggota setelah penilaian final.">{{ old('member_notes', $assessment?->member_notes) }}</textarea>

                <label class="form-label fw-semibold mt-3" for="general_notes">Catatan internal <span class="text-muted fw-normal">(opsional)</span></label>
                <textarea id="general_notes" name="general_notes" rows="3" maxlength="2000" class="form-control"
                          placeholder="Hanya terlihat oleh penilai dan pemeriksa.">{{ old('general_notes', $assessment?->general_notes) }}</textarea>
            </div>
        </div>

        @if (! $d)
            <div style="height:130px;"></div>
        @endif

        {{-- Bar aksi --}}
        <div class="action-bar {{ $d ? 'is-desktop' : 'is-mobile' }}">
            <div class="small mb-2" id="estimate">Perkiraan nilai: –</div>
            <div class="btns">
                <button type="submit" name="action" value="draft" class="btn btn-outline-primary">
                    <i class="bi bi-save me-1"></i>Simpan Draft
                </button>
                <button type="submit" name="action" value="submit" class="btn btn-primary" id="btnSubmit">
                    <i class="bi bi-send me-1"></i>Kirim
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const grades = @json(collect(config('assessment.grades'))->sortByDesc('min')->values());
            const boxes = document.querySelectorAll('.criterion');
            const out = document.getElementById('estimate');

            function gradeFor(score) {
                for (const g of grades) {
                    if (score >= g.min) return g.label;
                }
                return grades.length ? grades[grades.length - 1].label : '';
            }

            function calc() {
                let weightSum = 0, acc = 0, filled = 0;

                boxes.forEach(function (box) {
                    const w = parseFloat(box.dataset.weight);
                    const min = parseFloat(box.dataset.min);
                    const max = parseFloat(box.dataset.max);
                    const checked = box.querySelector('input[type="radio"]:checked');

                    weightSum += w;
                    if (checked) {
                        filled++;
                        acc += ((parseFloat(checked.value) - min) / ((max - min) || 1)) * 100 * w;
                    }
                });

                if (filled === boxes.length && weightSum > 0) {
                    const score = acc / weightSum;
                    out.innerHTML = 'Perkiraan nilai: <strong>' + score.toFixed(2).replace('.', ',') + '</strong> · ' + gradeFor(score);
                } else {
                    out.innerHTML = 'Perkiraan nilai: <strong>' + filled + '/' + boxes.length + '</strong> kriteria dipilih';
                }

                return filled === boxes.length;
            }

            document.querySelectorAll('.criterion input[type="radio"]').forEach(function (r) {
                r.addEventListener('change', calc);
            });

            document.getElementById('btnSubmit').addEventListener('click', function (e) {
                if (!calc()) {
                    e.preventDefault();
                    alert('Pilih skor untuk semua kriteria sebelum mengirim penilaian.');
                    return;
                }
                if (!confirm('Kirim penilaian ini? Setelah dikirim, penilaian tidak dapat diubah kecuali dikembalikan untuk revisi.')) {
                    e.preventDefault();
                }
            });

            calc();
        })();
    </script>
@endpush
