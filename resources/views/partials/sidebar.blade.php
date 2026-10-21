@php
    $u = auth()->user();
    $home = $u->homeRoute();
    $unread = $u->unreadNotifications()->count();
    $on = fn (string ...$patterns) => request()->routeIs(...$patterns) ? 'active' : '';
@endphp

<div class="d-flex flex-column h-100">
    <div class="sidebar-brand d-flex align-items-center gap-2 px-3 py-3">
        <img src="{{ asset('images/logo-mark.png') }}" alt="Logo Binpers" width="40" height="40" class="flex-shrink-0">
        <div class="lh-sm">
            <div class="fw-semibold text-uppercase" style="letter-spacing:.08em;">
                {{ config('app.name') }}
            </div>
            <small class="opacity-75">Penilaian Kinerja Personel</small>
        </div>
    </div>

    <nav class="nav flex-column px-2 py-3 flex-grow-1 overflow-auto flex-nowrap">
        <a href="{{ route($home) }}" class="sidebar-link {{ $on($home) }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        @canany(['assessments.approve', 'reports.view'])
            <div class="sidebar-section">Pemantauan</div>

            <a href="{{ route('review.index') }}" class="sidebar-link {{ $on('review.*') }}">
                <i class="bi bi-clipboard-check"></i> Pemeriksaan
            </a>

            @can('assessments.approve')
                @php $notePending = app(\App\Services\ReviewRoutingService::class)->noteQueueFor(auth()->user())->count(); @endphp
                <a href="{{ route('note-reviews.index') }}" class="sidebar-link {{ $on('note-reviews.*') }}">
                    <i class="bi bi-check2-square"></i> Persetujuan Harian
                    @if ($notePending) <span class="badge text-bg-danger ms-auto">{{ $notePending }}</span> @endif
                </a>
            @endcan

            @can('reports.view')
                @role('hrd')
                    <a href="{{ route('notes.create') }}" class="sidebar-link {{ $on('notes.create') }}">
                        <i class="bi bi-pencil-square"></i> Tulis Catatan
                    </a>
                @endrole

                <a href="{{ route('reports.index') }}" class="sidebar-link {{ $on('reports.*') }}">
                    <i class="bi bi-file-earmark-bar-graph"></i> Laporan
                </a>

                <a href="{{ route('recap.index') }}" class="sidebar-link {{ $on('recap.*') }}">
                    <i class="bi bi-calendar-check"></i> Rekap Nilai
                </a>

                {{-- HRD tetap perlu: kelola catatan & riwayat pelanggaran. Pimpinan lain lewat Menilai Anggota. --}}
                @role('hrd')
                    <a href="{{ route('notes.index') }}" class="sidebar-link {{ $on('notes.index', 'notes.edit') }}">
                        <i class="bi bi-journal-text"></i> Catatan Kinerja
                    </a>
                @endrole
            @endcan
        @endcanany

        @can('assessments.create')
            <div class="sidebar-section">Penilaian</div>

            <a href="{{ route('assessor.assessments.index') }}"
               class="sidebar-link {{ $on('assessor.assessments.index', 'assessor.assessments.form', 'assessor.assessments.save', ...(auth()->user()->hasRole('hrd') ? [] : ['notes.index', 'notes.create', 'notes.edit'])) }}">
                <i class="bi bi-pencil-square"></i> Menilai Anggota
            </a>

            <a href="{{ route('assessor.assessments.history') }}"
               class="sidebar-link {{ $on('assessor.assessments.history', 'assessor.assessments.show') }}">
                <i class="bi bi-clock-history"></i> Riwayat Rekap Saya
            </a>
        @endcan

        @canany(['personnel.manage', 'organization.manage', 'positions.manage', 'users.manage'])
            <div class="sidebar-section">Data Induk</div>

            @can('personnel.manage')
                <a href="{{ route('hrd.personnel.index') }}" class="sidebar-link {{ $on('hrd.personnel.*') }}">
                    <i class="bi bi-people"></i> Personel
                </a>
            @endcan

            @can('organization.manage')
                <a href="{{ route('hrd.units.index') }}" class="sidebar-link {{ $on('hrd.units.*') }}">
                    <i class="bi bi-diagram-3"></i> Struktur Organisasi
                </a>
            @endcan

            @can('positions.manage')
                <a href="{{ route('hrd.positions.index') }}" class="sidebar-link {{ $on('hrd.positions.*') }}">
                    <i class="bi bi-briefcase"></i> Jabatan
                </a>
            @endcan

            @can('users.manage')
                <a href="{{ route('hrd.users.index') }}" class="sidebar-link {{ $on('hrd.users.*') }}">
                    <i class="bi bi-person-gear"></i> Akun Pengguna
                </a>
            @endcan
        @endcanany

        @canany(['authorizations.manage', 'periods.manage', 'rewards.manage'])
            <div class="sidebar-section">Pengaturan Penilaian</div>

            @can('authorizations.manage')
                <a href="{{ route('hrd.authorizations.index') }}" class="sidebar-link {{ $on('hrd.authorizations.*') }}">
                    <i class="bi bi-person-check"></i> Kewenangan Penilai
                </a>
            @endcan

            @can('periods.manage')
                <a href="{{ route('hrd.periods.index') }}" class="sidebar-link {{ $on('hrd.periods.*', 'hrd.criteria.*') }}">
                    <i class="bi bi-calendar-range"></i> Periode &amp; Kriteria
                </a>
            @endcan

            @can('periods.manage')
                <a href="{{ route('hrd.note-points.edit') }}" class="sidebar-link {{ $on('hrd.note-points.*') }}">
                    <i class="bi bi-sliders"></i> Poin Catatan
                </a>
            @endcan
        @endcanany

        @can('audit-logs.view')
            <div class="sidebar-section">Sistem</div>

            <a href="{{ route('hrd.audit-logs.index') }}" class="sidebar-link {{ $on('hrd.audit-logs.*') }}">
                <i class="bi bi-journal-text"></i> Audit Log
            </a>
        @endcan

        <div class="sidebar-section">Akun</div>

        @if ($u->personnel)
            <a href="{{ route('notes.mine') }}" class="sidebar-link {{ $on('notes.mine') }}">
                <i class="bi bi-journal-check"></i> Catatan untuk Saya
            </a>
        @endif

        <a href="{{ route('notifications.index') }}" class="sidebar-link {{ $on('notifications.*') }}">
            <i class="bi bi-bell"></i> Notifikasi

            @if ($unread)
                <span class="badge rounded-pill text-bg-danger ms-auto">
                    {{ $unread > 99 ? '99+' : $unread }}
                </span>
            @endif
        </a>

        <a href="{{ route('profile.show') }}" class="sidebar-link {{ $on('profile.*') }}">
            <i class="bi bi-person"></i> Profil
        </a>
    </nav>

    <div class="sidebar-footer px-3 py-3 small">
        <i class="bi bi-person-badge me-1"></i> {{ $u->roleLabel() }}
    </div>
</div>
