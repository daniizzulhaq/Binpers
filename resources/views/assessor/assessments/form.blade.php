@extends(auth()->user()->layout())

@section('title', 'Form Penilaian')
@section('page-title', 'Form Penilaian')

@section('content')
    @php
        $desktop = auth()->user()->usesDesktopLayout();
        $assignment = $assessee->currentAssignment;
    @endphp

    <a href="{{ route('assessor.assessments.index') }}" class="btn btn-link px-0 mb-2">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <div class="h5 mb-0">{{ $assessee->user->name }}</div>
                    <div class="small text-muted">
                        {{ $assessee->nrp }}@if ($assessee->rank) · {{ $assessee->rank }}@endif
                    </div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-diagram-3 me-1"></i>{{ $assignment?->unit?->name ?? '—' }}
                        @if ($assignment?->position) · {{ $assignment->position->name }} @endif
                    </div>
                </div>
                <div class="text-md-end">
                    <div class="small text-muted">Periode</div>
                    <div class="fw-semibold">{{ $period->name }}</div>
                    @if ($assessment)
                        <span class="badge text-bg-{{ $assessment->status->color() }} mt-1">{{ $assessment->status->label() }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($revisionNotes && $assessment?->status->value === 'revision')
        <div class="alert alert-danger">
            <div class="fw-semibold"><i class="bi bi-arrow-return-left me-1"></i>Dikembalikan untuk revisi</div>
            <div class="small mt-1">{{ $revisionNotes }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Periksa kembali isian Anda:</div>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('assessor.assessments.save', [$period, $assessee]) }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            @foreach ($criteria as $c)
                @php
                    $d = $details->get($c->id);
                    $val = old("scores.{$c->id}", $d?->score);
                @endphp
                <div class="{{ $desktop ? 'col-12 col-xl-6' : 'col-12' }}">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $c->name }}</div>
                                    @if ($c->description)
                                        <div class="small text-muted">{{ $c->description }}</div>
                                    @endif
                                </div>
                                <span class="badge text-bg-light border text-muted">Bobot {{ (float) $c->weight }}</span>
                            </div>

                            <label class="form-label small mb-1" for="score-{{ $c->id }}">
                                Skor ({{ (float) $c->min_score }} – {{ (float) $c->max_score }})
                            </label>
                            <input type="number" inputmode="decimal" step="0.01"
                                   id="score-{{ $c->id }}"
                                   name="scores[{{ $c->id }}]"
                                   value="{{ $val }}"
                                   min="{{ (float) $c->min_score }}" max="{{ (float) $c->max_score }}"
                                   data-weight="{{ (float) $c->weight }}"
                                   data-min="{{ (float) $c->min_score }}"
                                   data-max="{{ (float) $c->max_score }}"
                                   class="form-control form-control-lg score-input @error("scores.{$c->id}") is-invalid @enderror">
                            @error("scores.{$c->id}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <label class="form-label small mt-3 mb-1" for="note-{{ $c->id }}">Catatan (opsional)</label>
                            <textarea id="note-{{ $c->id }}" name="notes[{{ $c->id }}]" rows="2" maxlength="1000"
                                      class="form-control @error("notes.{$c->id}") is-invalid @enderror">{{ old("notes.{$c->id}", $d?->note) }}</textarea>
                            @error("notes.{$c->id}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <label class="form-label small mt-3 mb-1" for="evidence-{{ $c->id }}">
                                Bukti pendukung (JPG, PNG, PDF, maks {{ round(config('assessment.evidence_max_kb') / 1024, 1) }} MB)
                            </label>
                            <input type="file" id="evidence-{{ $c->id }}" name="evidence[{{ $c->id }}]"
                                   accept=".jpg,.jpeg,.png,.pdf"
                                   class="form-control @error("evidence.{$c->id}") is-invalid @enderror">
                            @error("evidence.{$c->id}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if ($d?->evidence_path)
                                <div class="d-flex flex-wrap align-items-center gap-3 mt-2 small">
                                    <a href="{{ route('evidence.show', $d) }}" target="_blank" rel="noopener">
                                        <i class="bi bi-paperclip me-1"></i>Lihat bukti tersimpan
                                    </a>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" value="1"
                                               name="remove_evidence[{{ $c->id }}]" id="rm-{{ $c->id }}">
                                        <label class="form-check-label" for="rm-{{ $c->id }}">Hapus bukti</label>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-xl-6">
                                <label class="form-label" for="general_notes">Catatan internal</label>
                                <textarea id="general_notes" name="general_notes" rows="3" maxlength="2000"
                                          class="form-control @error('general_notes') is-invalid @enderror">{{ old('general_notes', $assessment?->general_notes) }}</textarea>
                                <div class="form-text">Tidak terlihat oleh anggota.</div>
                                @error('general_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-xl-6">
                                <label class="form-label" for="member_notes">Catatan untuk anggota</label>
                                <textarea id="member_notes" name="member_notes" rows="3" maxlength="1000"
                                          class="form-control @error('member_notes') is-invalid @enderror">{{ old('member_notes', $assessment?->member_notes) }}</textarea>
                                <div class="form-text">Terlihat oleh anggota setelah penilaian final.</div>
                                @error('member_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-bar {{ $desktop ? '' : 'action-bar-mobile' }} mt-3">
            <div class="me-auto small">
                <span class="text-muted">Perkiraan nilai:</span>
                <strong id="estimate">—</strong>
            </div>
            <button type="submit" name="action" value="draft" class="btn btn-outline-primary">
                <i class="bi bi-save me-1"></i>Simpan Draft
            </button>
            <button type="submit" name="action" value="submit" class="btn btn-primary"
                    onclick="return confirm('Kirim penilaian untuk diperiksa? Setelah dikirim, penilaian tidak dapat diubah kecuali dikembalikan.')">
                <i class="bi bi-send me-1"></i>Kirim Penilaian
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const inputs = Array.from(document.querySelectorAll('.score-input'));
            const out = document.getElementById('estimate');

            function calc() {
                let sum = 0, total = 0, filled = 0;
                inputs.forEach(function (i) {
                    const w = parseFloat(i.dataset.weight);
                    const min = parseFloat(i.dataset.min);
                    const max = parseFloat(i.dataset.max);
                    const v = parseFloat(i.value);
                    total += w;
                    if (isNaN(v) || max <= min) return;
                    filled++;
                    sum += ((v - min) / (max - min)) * 100 * w;
                });
                out.textContent = (filled === inputs.length && total > 0)
                    ? (sum / total).toFixed(2)
                    : filled + '/' + inputs.length + ' terisi';
            }

            inputs.forEach(function (i) { i.addEventListener('input', calc); });
            calc();
        })();
    </script>
@endpush
