@extends(auth()->user()->layout())

@section('title', 'Persetujuan Penilaian Harian')
@section('page-title', 'Persetujuan Penilaian Harian')

@section('content')
    @php
        $u = auth()->user();
        $d = $u->usesDesktopLayout();
        // Halaman persetujuan rekap bulanan yang berpasangan dengan halaman ini.
        $recapRoute = ! $d && $u->can('assessments.review-structural') ? 'assessor.reviews.index' : 'review.index';
        $tabs = ['menunggu' => 'Menunggu', 'diproses' => 'Sudah saya proses'];
    @endphp

    @if (! $d)
        <section class="bp-hero">
            <div class="bp-hero-star"><x-icon name="check-circle" :size="104" :stroke="1" /></div>
            <div class="bp-hero-greet">Persetujuan</div>
            <div class="bp-hero-name">Penilaian Harian</div>
            <div class="bp-hero-meta">Setujui atau tolak apresiasi/teguran dari bawahan Anda. Poin baru dihitung setelah disetujui.</div>
        </section>
    @endif

    {{-- Pindah antara persetujuan rekap bulanan dan penilaian harian --}}
    <div class="btn-group w-100 mb-3" role="group">
        <a href="{{ route($recapRoute) }}" class="btn btn-outline-primary">Rekap Bulanan</a>
        <a href="{{ route('note-reviews.index') }}" class="btn btn-primary">
            Penilaian Harian
            @if ($pendingCount) <span class="badge text-bg-light ms-1">{{ $pendingCount }}</span> @endif
        </a>
    </div>

    <ul class="nav nav-pills nav-fill gap-1 mb-3">
        @foreach ($tabs as $key => $label)
            <li class="nav-item">
                <a href="{{ route('note-reviews.index', ['tab' => $key]) }}"
                   class="nav-link py-2 {{ $tab === $key ? 'active' : 'bg-white border' }}">
                    {{ $label }}
                    @if ($key === 'menunggu')
                        <span class="badge {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }} ms-1">{{ $pendingCount }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    @if ($errors->any())
        <div class="alert alert-danger small">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($tab === 'menunggu' && $notes->isNotEmpty())
        <form id="bulkForm" method="POST" action="{{ route('note-reviews.approve-many') }}"
              class="d-flex align-items-center justify-content-between gap-2 mb-2"
              onsubmit="return document.querySelectorAll('.note-check:checked').length ? confirm('Setujui semua penilaian yang dipilih?') : (alert('Pilih minimal satu penilaian.'), false)">
            @csrf
            <label class="form-check small mb-0">
                <input type="checkbox" class="form-check-input" id="checkAll">
                <span class="form-check-label">Pilih semua</span>
            </label>
            <button class="btn btn-sm btn-success"><i class="bi bi-check2-all me-1"></i>Setujui yang dipilih</button>
        </form>
    @endif

    @forelse ($notes as $n)
        @php $canReview = $tab === 'menunggu' && $u->can('review', $n); @endphp
        <div class="{{ $d ? 'card border-0 shadow-sm' : 'bp-card' }} p-3 mb-2">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="d-flex gap-2 min-w-0">
                    @if ($canReview)
                        <input type="checkbox" class="form-check-input mt-1 flex-shrink-0 note-check"
                               name="ids[]" value="{{ $n->id }}" form="bulkForm" aria-label="Pilih">
                    @endif
                    <div class="min-w-0">
                        <div class="fw-semibold text-break">{{ $n->subject?->user?->name ?? '—' }}</div>
                        <div class="small text-muted">NRP {{ $n->subject?->nrp }} · {{ $n->unit?->name ?? '—' }}</div>
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <span class="badge text-bg-{{ $n->type->color() }}">{{ $n->type->shortLabel() }}</span>
                    <div class="fw-bold {{ $n->points >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $n->points > 0 ? '+' : '' }}{{ $n->points }} poin
                    </div>
                </div>
            </div>

            <div class="mt-2">
                <span class="badge rounded-pill text-bg-light border">{{ $n->criterionName() }}</span>
                @if ($n->levelLabel()) <span class="badge rounded-pill text-bg-light border">{{ $n->levelLabel() }}</span> @endif
                @if ($tab === 'diproses')
                    <span class="badge rounded-pill text-bg-{{ $n->status->color() }}">{{ $n->status->label() }}</span>
                @endif
            </div>

            <div class="mt-2 text-break">
                <div class="fw-semibold">{{ $n->title }}</div>
                @if ($n->note) <div class="small text-muted">{{ $n->note }}</div> @endif
                @if ($n->image_path)
                    <a href="{{ route('notes.image', $n) }}" target="_blank" class="small text-decoration-none">
                        <i class="bi bi-image me-1"></i>Lihat foto
                    </a>
                @endif
            </div>

            <div class="small text-muted mt-2 text-break">
                <x-icon name="user" :size="13" class="me-1" />Penilai {{ $n->authorName() }}
                @if ($n->authorPosition()) ({{ $n->authorPosition() }}) @endif
                · <x-icon name="calendar" :size="13" class="me-1" />{{ $n->incident_date->format('d/m/Y') }}
                @if ($tab === 'diproses' && $n->reviewed_at)
                    · diproses {{ $n->reviewed_at->format('d/m/Y H:i') }}
                @endif
            </div>

            @if ($tab === 'diproses' && $n->status === \App\Enums\NoteStatus::Rejected && $n->review_notes)
                <div class="small text-danger mt-1 text-break">Alasan ditolak: {{ $n->review_notes }}</div>
            @endif

            @if ($canReview)
                <div class="d-grid gap-2 mt-3" style="grid-template-columns: 1fr 1fr;">
                    <form method="POST" action="{{ route('note-reviews.approve', $n) }}">
                        @csrf
                        <button class="btn btn-success w-100"><i class="bi bi-check-lg me-1"></i>Setujui</button>
                    </form>
                    <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse"
                            data-bs-target="#reject{{ $n->id }}" aria-expanded="false">
                        <i class="bi bi-x-lg me-1"></i>Tolak
                    </button>
                </div>
                <form method="POST" action="{{ route('note-reviews.reject', $n) }}" class="collapse mt-2" id="reject{{ $n->id }}">
                    @csrf
                    <textarea name="reason" rows="2" maxlength="500" class="form-control mb-2" required
                              placeholder="Alasan ditolak (dikirim ke penilai)"></textarea>
                    <button class="btn btn-danger w-100">Kirim penolakan</button>
                </form>
            @endif
        </div>
    @empty
        <div class="{{ $d ? 'card border-0 shadow-sm' : 'bp-card' }} p-4 text-center text-muted">
            <x-icon name="check-circle" :size="40" :stroke="1.4" class="d-block mx-auto mb-2" />
            {{ $tab === 'menunggu'
                ? 'Tidak ada penilaian harian yang menunggu persetujuan Anda.'
                : 'Belum ada penilaian harian yang Anda proses.' }}
        </div>
    @endforelse

    <div class="mt-3">{{ $notes->links() }}</div>
@endsection

@push('scripts')
    <script>
        document.getElementById('checkAll')?.addEventListener('change', (e) => {
            document.querySelectorAll('.note-check').forEach((c) => { c.checked = e.target.checked; });
        });
    </script>
@endpush
