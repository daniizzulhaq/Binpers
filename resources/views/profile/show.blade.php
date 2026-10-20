@extends(auth()->user()->layout())

@section('title', 'Profil')
@section('page-title', 'Profil')

@section('content')
    @php
        $u = auth()->user();
        $d = $u->usesDesktopLayout();
        $p = $u->personnel;
        $p?->loadMissing(['assignments.unit', 'assignments.position', 'currentAssignment.unit', 'currentAssignment.position']);
        $cur = $p?->currentAssignment;
        $initials = collect(explode(' ', trim($u->name)))->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
        $dash = '—';
    @endphp

    {{-- Kartu identitas --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
                 style="width:64px;height:64px;font-size:1.4rem;">
                {{ $initials ?: '?' }}
            </div>
            <div class="min-w-0">
                <div class="h5 fw-bold mb-1 text-break">{{ $u->name }}</div>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge text-bg-primary">{{ $u->roleLabel() }}</span>
                    <span class="badge text-bg-{{ $u->is_active ? 'success' : 'secondary' }}">
                        {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                @if ($p)
                    <div class="small text-muted mt-1 text-break">
                        {{ $p->rank ?: '' }}{{ $p->rank ? ' · ' : '' }}NRP {{ $p->nrp }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($p)
        {{-- Ringkasan cepat --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="small text-muted"><i class="bi bi-coin me-1"></i>Total Poin</div>
                        <div class="fs-3 fw-bold text-primary lh-1 mt-1">{{ number_format($p->totalPoints()) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="small text-muted"><i class="bi bi-diagram-3 me-1"></i>Unit Saat Ini</div>
                        <div class="fw-semibold lh-sm mt-1 text-break">{{ $cur?->unit?->name ?? $dash }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Data kepegawaian --}}
            <div class="{{ $d ? 'col-lg-6' : 'col-12' }}">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-person-vcard me-1"></i>Data Kepegawaian
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ([
                            'Jabatan' => $cur?->position?->name ?? $dash,
                            'Pangkat' => $p->rank ?: $dash,
                            'NRP' => $p->nrp,
                            'Tanggal bergabung' => $p->join_date?->format('d/m/Y') ?? $dash,
                        ] as $label => $value)
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ $label }}</span>
                                <span class="fw-semibold text-end text-break">{{ $value }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Data pribadi & kontak --}}
            <div class="{{ $d ? 'col-lg-6' : 'col-12' }}">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-person-lines-fill me-1"></i>Data Pribadi &amp; Kontak
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ([
                            'Username' => $u->username,
                            'Email' => $u->email ?: $dash,
                            'Telepon' => $p->phone ?: $dash,
                            'Jenis kelamin' => match ($p->gender) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => $dash },
                            'Tempat, tgl lahir' => trim(($p->place_of_birth ?: '').($p->birth_date ? ', '.$p->birth_date->format('d/m/Y') : ''), ', ') ?: $dash,
                            'Alamat' => $p->address ?: $dash,
                        ] as $label => $value)
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted flex-shrink-0">{{ $label }}</span>
                                <span class="fw-semibold text-end text-break">{{ $value }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Riwayat penempatan --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-clock-history me-1"></i>Riwayat Penempatan
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($p->assignments as $a)
                            <li class="list-group-item">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-1">
                                    <div class="fw-semibold text-break">{{ $a->position->name }}</div>
                                    @if ($a->end_date === null)
                                        <span class="badge text-bg-success">Saat ini</span>
                                    @endif
                                </div>
                                <div class="small text-muted text-break">{{ $a->unit->name }}</div>
                                <div class="small text-muted">
                                    {{ $a->start_date->format('d/m/Y') }} – {{ $a->end_date?->format('d/m/Y') ?? 'sekarang' }}
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-4">Belum ada riwayat penempatan.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @else
        {{-- Akun tanpa data personel (mis. akun HRD yang belum dibuatkan personelnya) --}}
        <div class="card border-0 shadow-sm mb-3">
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between gap-3">
                    <span class="text-muted">Username</span>
                    <span class="fw-semibold text-break">{{ $u->username }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between gap-3">
                    <span class="text-muted">Email</span>
                    <span class="fw-semibold text-break">{{ $u->email ?: $dash }}</span>
                </li>
            </ul>
        </div>
        <div class="alert alert-info small">Data personel belum dibuat untuk akun ini. Hubungi HRD.</div>
    @endif

    {{-- Keluar (menu bawah mobile tidak punya tombol Keluar) --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-3 {{ $d ? '' : 'd-grid' }}">
        @csrf
        <button type="submit" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right me-1"></i>Keluar
        </button>
    </form>
@endsection
