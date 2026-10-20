@extends(auth()->user()->layout())

@section('title', 'Catatan untuk Saya')
@section('page-title', 'Catatan untuk Saya')

@section('content')
    @php
        $d = auth()->user()->usesDesktopLayout();
        $positive = (int) ($counts['positive'] ?? 0);
        $negative = (int) ($counts['negative'] ?? 0);
        $violation = (int) ($counts['violation'] ?? 0);
    @endphp

    <div class="row g-2 mb-3">
        <div class="{{ $d ? 'col-4 col-md-3' : 'col-4' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="small text-muted d-flex align-items-center gap-1">
                        <x-icon name="thumbs-up" :size="16" class="text-success" />Apresiasi
                    </div>
                    <div class="fs-3 fw-bold text-success lh-1 mt-1">{{ $positive }}</div>
                </div>
            </div>
        </div>
        <div class="{{ $d ? 'col-4 col-md-3' : 'col-4' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="small text-muted d-flex align-items-center gap-1">
                        <x-icon name="alert" :size="16" style="color:#d97706;" />Teguran
                    </div>
                    <div class="fs-3 fw-bold lh-1 mt-1" style="color:#b45309;">{{ $negative }}</div>
                </div>
            </div>
        </div>
        <div class="{{ $d ? 'col-4 col-md-3' : 'col-4' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="small text-muted d-flex align-items-center gap-1">
                        <x-icon name="file-alert" :size="16" class="text-danger" />Pelanggaran
                    </div>
                    <div class="fs-3 fw-bold text-danger lh-1 mt-1">{{ $violation }}</div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="{{ $d ? 'col-8 col-md-4 col-xl-3' : 'col-8' }}">
            <select name="type" class="form-select" aria-label="Jenis catatan">
                <option value="">Semua jenis</option>
                @foreach ($types as $t)
                    <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="{{ $d ? 'col-4 col-md-2' : 'col-4' }}">
            <button class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    @forelse ($notes as $n)
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">
                    <span class="badge text-bg-{{ $n->type->color() }}">
                        <x-icon :name="$n->type->svg()" :size="12" :stroke="2.2" class="me-1" />{{ $n->type->label() }}
                    </span>
                    <span class="small text-muted">{{ $n->incident_date->format('d/m/Y') }}</span>
                </div>

                <div class="fw-semibold text-break">{{ $n->title }}</div>

                <p class="mb-2 mt-1 text-break">{!! nl2br(e($n->note)) !!}</p>
                @include('notes._image', ['note' => $n])
                <div class="small text-muted border-top pt-2">
                    <x-icon name="user" :size="13" class="me-1" />Dicatat oleh
                    <strong>{{ $n->authorName() }}</strong>
                    @if ($n->authorPosition())
                        ({{ $n->authorPosition() }})
                    @endif
                    <div>{{ $n->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <x-icon name="notebook" :size="40" :stroke="1.4" class="mb-2" />
                <div>Belum ada catatan untuk Anda.</div>
            </div>
        </div>
    @endforelse

    <div class="mt-3">{{ $notes->links() }}</div>
@endsection
