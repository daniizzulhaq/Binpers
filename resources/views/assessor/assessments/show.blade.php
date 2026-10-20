@extends(auth()->user()->layout())

@section('title', 'Detail Penilaian')
@section('page-title', 'Detail Penilaian')

@section('content')
    @php
        $a = $assessment;
        $rows = $a->details->sortBy(fn ($d) => $d->criterion->sort_order);
        $returned = $a->approvals->firstWhere('action', \App\Enums\ApprovalAction::Return);
    @endphp

    <a href="{{ route('assessor.assessments.history') }}" class="btn btn-link px-0 mb-2">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke riwayat
    </a>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <div class="h5 mb-0">{{ $a->assessee->user->name }}</div>
                    <div class="small text-muted">{{ $a->assessee->nrp }}@if ($a->assessee->rank) · {{ $a->assessee->rank }}@endif</div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-diagram-3 me-1"></i>{{ $a->assesseeUnit?->name ?? '—' }}
                        @if ($a->assesseePosition) · {{ $a->assesseePosition->name }} @endif
                    </div>
                </div>
                <div class="text-md-end">
                    <div class="small text-muted">{{ $a->period->name }}</div>
                    <span class="badge text-bg-{{ $a->status->color() }}">{{ $a->status->label() }}</span>
                    @if ($a->final_score !== null)
                        <div class="mt-1"><span class="fs-4 fw-bold text-primary">{{ $a->final_score }}</span>
                            <span class="text-muted">({{ $a->grade }})</span></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($a->status->value === 'revision' && $returned?->notes)
        <div class="alert alert-danger">
            <div class="fw-semibold"><i class="bi bi-arrow-return-left me-1"></i>Catatan revisi</div>
            <div class="small mt-1">{{ $returned->notes }}</div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Skor per kriteria</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kriteria</th>
                        <th class="text-center">Bobot</th>
                        <th class="text-center">Skor</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $d)
                        <tr>
                            <td>{{ $d->criterion->name }}</td>
                            <td class="text-center">{{ (float) $d->criterion->weight }}</td>
                            <td class="text-center fw-semibold">{{ (float) $d->score }}</td>
                            <td class="small">
                                {{ $d->note ?: '—' }}
                                @if ($d->evidence_path)
                                    <div><a href="{{ route('evidence.show', $d) }}" target="_blank" rel="noopener">
                                        <i class="bi bi-paperclip me-1"></i>Bukti</a></div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($a->general_notes || $a->member_notes)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body small">
                @if ($a->general_notes)
                    <div class="fw-semibold">Catatan internal</div>
                    <p class="mb-3">{{ $a->general_notes }}</p>
                @endif
                @if ($a->member_notes)
                    <div class="fw-semibold">Catatan untuk anggota</div>
                    <p class="mb-0">{{ $a->member_notes }}</p>
                @endif
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Riwayat proses</div>
        <ul class="list-group list-group-flush">
            @forelse ($a->approvals as $ap)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="fw-semibold">{{ $ap->action->label() }}</span>
                        <span class="small text-muted">{{ $ap->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="small text-muted">oleh {{ $ap->user->name }}</div>
                    @if ($ap->notes)
                        <div class="small mt-1">{{ $ap->notes }}</div>
                    @endif
                </li>
            @empty
                <li class="list-group-item text-muted small">Belum ada proses.</li>
            @endforelse
        </ul>
    </div>

    <div class="d-flex flex-wrap gap-2">
        @can('update', $a)
            <a href="{{ route('assessor.assessments.form', [$a->assessment_period_id, $a->assessee_id]) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i>Ubah Penilaian
            </a>
        @endcan
        @can('submit', $a)
            <form method="POST" action="{{ route('assessor.assessments.submit', $a) }}">
                @csrf
                <button class="btn btn-primary"
                        onclick="return confirm('Kirim penilaian untuk diperiksa?')">
                    <i class="bi bi-send me-1"></i>Kirim Penilaian
                </button>
            </form>
        @endcan
    </div>
@endsection
