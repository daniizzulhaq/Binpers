@extends(auth()->user()->layout())

@section('title', 'Menilai Anggota')
@section('page-title', 'Menilai Anggota')

@section('content')
    @php
        $d = auth()->user()->usesDesktopLayout();
        $tabs = [
            'wajib' => 'Bawahan Langsung',
            'semua' => 'Semua',
        ];
    @endphp

    @if ($period)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3 d-flex justify-content-between align-items-center gap-2">
                <div class="min-w-0">
                    <div class="small text-muted">Periode dibuka</div>
                    <div class="fw-semibold text-break">{{ $period->name }}</div>
                </div>
                <span class="badge text-bg-success">Dibuka</span>
            </div>
        </div>
    @else
        <div class="alert alert-warning small">
            Belum ada periode penilaian yang dibuka. Anda tetap dapat menulis Catatan Kinerja.
        </div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-2">
        <a href="{{ route('notes.index') }}" class="btn btn-sm btn-outline-primary">
            <x-icon name="notebook" :size="16" class="me-1" />Penilaian harian saya
        </a>
        <a href="{{ route('assessor.assessments.history') }}" class="btn btn-sm btn-outline-primary">
            <x-icon name="history" :size="16" class="me-1" />Riwayat rekap
        </a>
    </div>

    {{-- Tab bawahan langsung / semua --}}
    <ul class="nav nav-pills nav-fill gap-1 mb-3">
        @foreach ($tabs as $key => $label)
            <li class="nav-item">
                <a href="{{ route('assessor.assessments.index', array_merge(request()->only('q', 'status'), ['kind' => $key])) }}"
                   class="nav-link py-2 {{ $kind === $key ? 'active' : 'bg-white border' }}">
                    {{ $label }}
                    <span class="badge {{ $kind === $key ? 'text-bg-light' : 'text-bg-secondary' }} ms-1">{{ $counts[$key] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <form method="GET" class="row g-2 mb-3">
        <input type="hidden" name="kind" value="{{ $kind }}">
        <div class="{{ $d ? 'col-12 col-md-6' : 'col-12' }}">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau NRP">
        </div>
        <div class="{{ $d ? 'col-8 col-md-4' : 'col-8' }}">
            <select name="status" class="form-select" aria-label="Status penilaian">
                <option value="">Semua status</option>
                <option value="belum" @selected(request('status') === 'belum')>Rekap belum dikirim</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="{{ $d ? 'col-4 col-md-2' : 'col-4' }}">
            <button class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="row g-2">
        @forelse ($people as $p)
            @php
                $a = $assessed->get($p->id);
                $cur = $p->currentAssignment;
                $isWajib = $mandatory->has($p->id);
            @endphp
            <div class="{{ $d ? 'col-12 col-lg-6' : 'col-12' }}">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold text-break">{{ $p->user->name }}</div>
                                <div class="small text-muted">{{ $p->rank ?: '-' }} · NRP {{ $p->nrp }}</div>
                            </div>
                            <span class="badge {{ $isWajib ? 'text-bg-primary' : 'text-bg-light border' }}">
                                {{ $isWajib ? 'Bawahan langsung' : 'Lintas unit' }}
                            </span>
                        </div>

                        <div class="small text-muted mt-1 text-break">
                            {{ $cur?->position?->name ?? '-' }} · {{ $cur?->unit?->name ?? '-' }}
                        </div>

                        @php
                            $sum = $summary->get($p->id);
                            // Tampilan mobile (Kanit, Kasi, dst.): nilai hanya terlihat untuk bawahan langsung.
                            $showScore = $d || $isWajib;
                        @endphp

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            @if ($a)
                                <span class="badge text-bg-{{ $a->status->color() }}">{{ $a->status->label() }}</span>
                            @else
                                <span class="badge text-bg-secondary">Rekap belum dikirim</span>
                            @endif

                            @if ($sum)
                                <span class="small text-muted">
                                    {{ $sum['count'] }} penilaian bulan ini
                                    @if ($showScore)
                                        · nilai <strong class="text-body">{{ rtrim(rtrim(number_format($sum['final'], 2, ',', '.'), '0'), ',') }}</strong>
                                    @endif
                                </span>
                                @if ($sum['pending'])
                                    <span class="badge text-bg-warning">{{ $sum['pending'] }} menunggu persetujuan</span>
                                @endif
                                @if ($sum['rejected'])
                                    <span class="badge text-bg-danger">{{ $sum['rejected'] }} ditolak</span>
                                @endif
                            @endif
                        </div>

                        @if ($period)
                            <div class="d-flex gap-2 mt-3">
                                @if ($a && ! $a->status->editable())
                                    <a href="{{ route('assessor.assessments.show', $a) }}" class="btn btn-sm btn-outline-primary flex-fill">Lihat Rekap</a>
                                @else
                                    <a href="{{ route('notes.create', ['subject_id' => $p->id]) }}" class="btn btn-sm btn-primary flex-fill">
                                        <x-icon name="pencil" :size="16" class="me-1" />Nilai
                                    </a>
                                    <a href="{{ route('assessor.assessments.form', [$period, $p]) }}" class="btn btn-sm btn-outline-primary flex-fill">Rekap</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5">Tidak ada personel pada daftar ini.</div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $people->links() }}</div>
@endsection
