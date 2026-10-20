@extends(auth()->user()->layout())

@section('title', 'Menilai Anggota')
@section('page-title', 'Menilai Anggota')

@section('content')
    @php
        $d = auth()->user()->usesDesktopLayout();
        $tabs = [
            'wajib' => 'Wajib',
            'lintas' => 'Lintas Unit',
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

    {{-- Tab wajib / lintas / semua --}}
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
                <option value="belum" @selected(request('status') === 'belum')>Belum dinilai</option>
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
                                {{ $isWajib ? 'Wajib' : 'Lintas unit' }}
                            </span>
                        </div>

                        <div class="small text-muted mt-1 text-break">
                            {{ $cur?->position?->name ?? '-' }} · {{ $cur?->unit?->name ?? '-' }}
                        </div>

                        <div class="mt-2">
                            @if ($a)
                                <span class="badge text-bg-{{ $a->status->color() }}">{{ $a->status->label() }}</span>
                            @else
                                <span class="badge text-bg-secondary">Belum dinilai</span>
                            @endif
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            @if ($a && ! $a->status->editable())
                                <a href="{{ route('assessor.assessments.show', $a) }}" class="btn btn-sm btn-outline-primary flex-fill">Lihat</a>
                            @elseif ($period)
                                <a href="{{ route('assessor.assessments.form', [$period, $p]) }}" class="btn btn-sm btn-primary flex-fill">
                                    {{ $a ? 'Lanjutkan' : 'Nilai' }}
                                </a>
                            @endif
                            <a href="{{ route('notes.create', ['subject_id' => $p->id]) }}" class="btn btn-sm btn-outline-secondary flex-fill">Catat</a>
                        </div>
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
