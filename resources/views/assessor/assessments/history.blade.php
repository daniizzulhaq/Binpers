@extends(auth()->user()->layout())

@section('title', 'Riwayat Penilaian')
@section('page-title', 'Riwayat Penilaian Saya')

@section('content')
    @php($desktop = auth()->user()->usesDesktopLayout())

    <form method="GET" class="row g-2 mb-3">
        <div class="col-6 col-md-4 col-xl-3">
            <select name="period_id" class="form-select">
                <option value="">Semua periode</option>
                @foreach ($periods as $p)
                    <option value="{{ $p->id }}" @selected((int) request('period_id') === $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    @if ($assessments->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                Belum ada riwayat penilaian.
            </div>
        </div>
    @elseif ($desktop)
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Anggota</th>
                            <th>Unit</th>
                            <th>Periode</th>
                            <th class="text-center">Nilai</th>
                            <th>Status</th>
                            <th>Diperbarui</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assessments as $a)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $a->assessee->user->name }}</div>
                                    <div class="small text-muted">{{ $a->assessee->nrp }}</div>
                                </td>
                                <td>{{ $a->assesseeUnit?->name ?? '—' }}</td>
                                <td>{{ $a->period->name }}</td>
                                <td class="text-center">
                                    @if ($a->final_score !== null)
                                        <strong>{{ $a->final_score }}</strong>
                                        <div class="small text-muted">{{ $a->grade }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $a->status->color() }}">{{ $a->status->label() }}</span></td>
                                <td class="small text-muted">{{ $a->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('assessor.assessments.show', $a) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="vstack gap-2">
            @foreach ($assessments as $a)
                <a href="{{ route('assessor.assessments.show', $a) }}" class="card border-0 shadow-sm text-decoration-none text-body">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">{{ $a->assessee->user->name }}</div>
                                <div class="small text-muted">{{ $a->period->name }}</div>
                            </div>
                            <span class="badge text-bg-{{ $a->status->color() }}">{{ $a->status->label() }}</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mt-2">
                            <span>{{ $a->assesseeUnit?->name ?? '—' }}</span>
                            <span>
                                @if ($a->final_score !== null)
                                    Nilai <strong class="text-body">{{ $a->final_score }}</strong> ({{ $a->grade }})
                                @endif
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <div class="mt-3">{{ $assessments->links() }}</div>
@endsection
