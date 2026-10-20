@php
    $u = auth()->user();

    $assessItems = [
        ['assessor.assessments.index', 'clipboard-check', 'Penilaian', ['assessor.assessments.index', 'assessor.assessments.form', 'assessor.assessments.save']],
        ['notes.index', 'notebook', 'Catatan', ['notes.*']],
        ['assessor.assessments.history', 'history', 'Riwayat', ['assessor.assessments.history', 'assessor.assessments.show']],
        ['profile.show', 'user', 'Profil', ['profile.*']],
    ];

    $items = match (true) {
        // Kapolres / Wakapolres: kembali ke dashboard desktop-nya
        $u->usesDesktopLayout() => [
            [$u->homeRoute(), 'dashboard', 'Dashboard', [$u->homeRoute()]],
            ...$assessItems,
        ],
        $u->isAssessor() => [
            ['assessor.home', 'home', 'Beranda', ['assessor.home']],
            ...$assessItems,
        ],
        default => [
            ['member.home', 'home', 'Beranda', ['member.home']],
            ['member.results.index', 'chart', 'Hasil', ['member.results.*']],
            ['notes.mine', 'notebook', 'Catatan', ['notes.mine']],
            ['profile.show', 'user', 'Profil', ['profile.*']],
        ],
    };
@endphp

<nav class="bp-nav" aria-label="Navigasi utama">
    <div class="bp-nav-inner">
        @foreach ($items as [$route, $icon, $label, $patterns])
            @php
                $active = request()->routeIs(...$patterns);
            @endphp
            <a href="{{ route($route) }}" class="{{ $active ? 'active' : '' }}">
                <x-icon :name="$icon" :size="24" :stroke="$active ? 2.1 : 1.6" />
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </div>
</nav>
