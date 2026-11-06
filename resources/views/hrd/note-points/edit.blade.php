@extends('layouts.app')

@section('title', 'Poin & Nilai Penilaian')
@section('page-title', 'Poin & Nilai Penilaian')

@section('content')
    <p class="text-muted small">
        Poin ditambahkan (Apresiasi) atau dikurangkan (Teguran) dari nilai bulanan anggota.
        Perubahan hanya berlaku untuk catatan baru. Riwayat Pelanggaran tidak memengaruhi poin.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger small">Periksa kembali isian poin (0–100).</div>
    @endif

    <form method="POST" action="{{ route('hrd.note-points.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            @foreach ([\App\Enums\NoteType::Positive, \App\Enums\NoteType::Negative] as $t)
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-semibold">
                            <span class="badge text-bg-{{ $t->color() }} me-1">{{ $t->label() }}</span>
                            {{ $t->sign() > 0 ? 'menambah nilai' : 'mengurangi nilai' }}
                        </div>
                        <div class="card-body">
                            @foreach (\App\Enums\NoteLevel::cases() as $l)
                                <div class="row align-items-center mb-2">
                                    <label class="col-5 col-form-label" for="p-{{ $t->value }}-{{ $l->value }}">{{ $l->label($t) }}</label>
                                    <div class="col-7">
                                        <div class="input-group">
                                            <span class="input-group-text">{{ $t->sign() > 0 ? '+' : '−' }}</span>
                                            <input type="number" min="0" max="100" required
                                                   id="p-{{ $t->value }}-{{ $l->value }}"
                                                   name="points[{{ $t->value }}][{{ $l->value }}]"
                                                   value="{{ old('points.'.$t->value.'.'.$l->value, $map[$t->value][$l->value] ?? 0) }}"
                                                   class="form-control">
                                            <span class="input-group-text">poin</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Simpan</button>
    </form>

    <hr class="my-4">

    <form method="POST" action="{{ route('hrd.scoring.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Ambang predikat nilai akhir (%)</div>
                    <div class="card-body">
                        <p class="small text-muted">Nilai minimum (0–100) untuk mendapat predikat. Harus menurun dari atas ke bawah; predikat terendah selalu mulai dari 0.</p>
                        @foreach ($grades as $i => $g)
                            @php $isLowest = $loop->last; @endphp
                            <div class="row align-items-center mb-2">
                                <label class="col-5 col-form-label" for="grade-{{ $i }}">{{ $g['label'] }}</label>
                                <div class="col-7">
                                    <div class="input-group">
                                        <span class="input-group-text">&ge;</span>
                                        <input type="number" min="0" max="100" step="0.01" required
                                               id="grade-{{ $i }}" name="grades[{{ $i }}]"
                                               value="{{ $isLowest ? 0 : old('grades.'.$i, $g['min'] + 0) }}"
                                               class="form-control" @readonly($isLowest)>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Nilai dasar penilaian harian</div>
                    <div class="card-body">
                        <p class="small text-muted">Nilai awal setiap kriteria (0–100) sebelum ditambah poin Apresiasi dan dikurangi poin Teguran pada bulan tersebut.</p>
                        <div class="input-group">
                            <input type="number" min="0" max="100" step="0.01" required id="base_score" name="base_score"
                                   value="{{ old('base_score', $baseScore + 0) }}" class="form-control">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="small text-muted mt-2 mb-0">Bobot (%) tiap kriteria diatur di menu <a href="{{ route('hrd.periods.index') }}">Periode &amp; Kriteria</a>.</p>
        <button class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Simpan pengaturan nilai</button>
    </form>
@endsection
