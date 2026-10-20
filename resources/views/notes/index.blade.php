@extends(auth()->user()->layout())

@section('title', 'Catatan Kinerja')
@section('page-title', 'Catatan Kinerja')

@section('content')
    @php
        $user = auth()->user();
        $d = $user->usesDesktopLayout();
        $canWrite = count($writeTypes) > 0;
        $writeLabel = 'Tulis Catatan';
        $positive = (int) ($counts['positive'] ?? 0);
        $negative = (int) ($counts['negative'] ?? 0);
        $violation = (int) ($counts['violation'] ?? 0);
    @endphp

    <div class="mb-3">
        <div class="text-muted small mb-2">
            @if ($mineOnly) Catatan yang Anda buat @else Seluruh catatan @endif
        </div>

        <div class="{{ $d ? 'd-flex gap-2 justify-content-end' : 'd-flex gap-2' }}">
            @if ($user->personnel)
                <a href="{{ route('notes.mine') }}" class="btn btn-outline-primary {{ $d ? '' : 'flex-fill' }}">
                    <i class="bi bi-journal-check me-1"></i>Untuk saya
                </a>
            @endif
            @if ($canWrite)
                <a href="{{ route('notes.create') }}" class="btn btn-primary {{ $d ? '' : 'flex-fill' }}">
                    <i class="bi bi-plus-lg me-1"></i>{{ $writeLabel }}
                </a>
            @endif
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="{{ $d ? 'col-4 col-md-3' : 'col-4' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="small text-muted"><i class="bi bi-hand-thumbs-up text-success me-1"></i>Apresiasi</div>
                    <div class="fs-3 fw-bold text-success lh-1 mt-1">{{ $positive }}</div>
                </div>
            </div>
        </div>
        <div class="{{ $d ? 'col-4 col-md-3' : 'col-4' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="small text-muted"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Teguran</div>
                    <div class="fs-3 fw-bold lh-1 mt-1" style="color:#b45309;">{{ $negative }}</div>
                </div>
            </div>
        </div>
        <div class="{{ $d ? 'col-4 col-md-3' : 'col-4' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="small text-muted"><i class="bi bi-file-earmark-x text-danger me-1"></i>Pelanggaran</div>
                    <div class="fs-3 fw-bold text-danger lh-1 mt-1">{{ $violation }}</div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="{{ $d ? 'col-12 col-md-6 col-xl-4' : 'col-12' }}">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama, NRP, atau judul">
            </div>
        </div>
        <div class="{{ $d ? 'col-6 col-md-3 col-xl-2' : 'col-12' }}">
            <select name="type" class="form-select" aria-label="Jenis catatan">
                <option value="">Semua jenis</option>
                @foreach ($types as $t)
                    <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="{{ $d ? 'col-6 col-md-3 col-xl-2' : 'col-6' }}">
            <input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="Dari tanggal">
        </div>
        <div class="{{ $d ? 'col-6 col-md-3 col-xl-2' : 'col-6' }}">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="Sampai tanggal">
        </div>
        @if ($seeAll && $canWrite)
            <div class="{{ $d ? 'col-6 col-md-3 col-xl-2 d-flex align-items-center' : 'col-12' }}">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="mine" value="1" id="mine" @checked(request()->boolean('mine'))>
                    <label class="form-check-label" for="mine">Buatan saya</label>
                </div>
            </div>
        @endif
        <div class="{{ $d ? 'col-12 col-md-2' : 'col-12' }}">
            <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </form>

    @forelse ($notes as $n)
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">
                    <span class="badge text-bg-{{ $n->type->color() }}">
                        <i class="bi {{ $n->type->icon() }} me-1"></i>{{ $n->type->label() }}
                    </span>
                    <span class="small text-muted">{{ $n->incident_date->format('d/m/Y') }}</span>
                </div>

                <div class="fw-semibold text-break">{{ $n->title }}</div>

                <div class="small text-muted mt-1 text-break">
                    <i class="bi bi-person me-1"></i>{{ $n->subject->user->name }} · {{ $n->subject->nrp }}
                    @if ($n->unit) · {{ $n->unit->name }} @endif
                </div>

                <p class="mb-2 mt-2 text-break">{!! nl2br(e($n->note)) !!}</p>
                                @include('notes._image', ['note' => $n])

                <div class="small text-muted border-top pt-2">
                    @if ($seeAll)
                        <div>
                            <i class="bi bi-pencil me-1"></i>Dicatat oleh <strong>{{ $n->authorName() }}</strong>
                            @if ($n->authorPosition()) ({{ $n->authorPosition() }}) @endif
                        </div>
                    @endif
                    <div>{{ $n->created_at->format('d/m/Y H:i') }}</div>

                    @can('update', $n)
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('notes.edit', $n) }}" class="btn btn-sm btn-outline-primary {{ $d ? '' : 'flex-fill' }}">
                                <i class="bi bi-pencil-square"></i> Ubah
                            </a>
                            <form method="POST" action="{{ route('notes.destroy', $n) }}"
                                  class="{{ $d ? '' : 'flex-fill d-flex' }}"
                                  onsubmit="return confirm('Hapus catatan ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger {{ $d ? '' : 'flex-fill' }}">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-journal-text fs-1 d-block mb-2"></i>
                Belum ada catatan.
            </div>
        </div>
    @endforelse

    <div class="mt-3">{{ $notes->links() }}</div>
@endsection
