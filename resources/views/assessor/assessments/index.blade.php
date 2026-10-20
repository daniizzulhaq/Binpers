@extends(auth()->user()->layout())

@section('title', 'Menilai Anggota')
@section('page-title', 'Menilai Anggota')

@section('content')
    @php
        $desktop = auth()->user()->usesDesktopLayout();
    @endphp

    @if (! $period)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Belum ada periode penilaian yang dibuka. Hubungi HRD.
        </div>
    @else
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 d-flex flex-wrap align-items-center gap-2">
                <div>
                    <div class="small text-muted">Periode aktif</div>
                    <div class="fw-semibold">{{ $period->name }}</div>
                </div>
                <div class="ms-auto small text-muted">
                    {{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}
                </div>
            </div>
        </div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-12 col-md-6 col-xl-5">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau NRP">
            </div>
        </div>
        <div class="col-8 col-md-4 col-xl-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                <option value="belum" @selected(request('status') === 'belum')>Belum dinilai</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4 col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="row g-3">
        @forelse ($authorizations as $auth)
            @php
                $p = $auth->assessee;
                $as = $assessed->get($p->id);
                $assignment = $p->currentAssignment;
            @endphp
            <div class="{{ $desktop ? 'col-12 col-md-6 col-xl-4' : 'col-12' }}">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold">{{ $p->user->name }}</div>
                                <div class="small text-muted">
                                    {{ $p->nrp }}@if ($p->rank) · {{ $p->rank }}@endif
                                </div>
                            </div>
                            @if ($as)
                                <span class="badge text-bg-{{ $as->status->color() }}">{{ $as->status->label() }}</span>
                            @else
                                <span class="badge text-bg-light border text-muted">Belum dinilai</span>
                            @endif
                        </div>

                        <div class="small text-muted mt-2">
                            <i class="bi bi-diagram-3 me-1"></i>{{ $assignment?->unit?->name ?? '—' }}
                            @if ($assignment?->position) · {{ $assignment->position->name }} @endif
                        </div>

                        @if ($as && $as->final_score !== null)
                            <div class="small mt-2">
                                Nilai: <strong>{{ $as->final_score }}</strong>
                                @if ($as->grade) <span class="text-muted">({{ $as->grade }})</span> @endif
                            </div>
                        @endif

                        <div class="mt-3">
                            @if ($as && ! $as->status->editable())
                                <a href="{{ route('assessor.assessments.show', $as) }}" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-eye me-1"></i>Lihat Penilaian
                                </a>
                            @elseif ($period)
                                <a href="{{ route('assessor.assessments.form', [$period, $p]) }}" class="btn btn-primary w-100">
                                    <i class="bi bi-pencil-square me-1"></i>
                                    {{ $as ? ($as->status->value === 'revision' ? 'Perbaiki Penilaian' : 'Lanjutkan Draft') : 'Beri Penilaian' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        Tidak ada anggota yang sesuai. Kewenangan menilai ditetapkan oleh HRD.
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $authorizations->links() }}</div>
@endsection
