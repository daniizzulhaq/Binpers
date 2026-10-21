@php
    $u = auth()->user();

    // Penilaian harian (dulu "Catatan") sudah menjadi bagian dari menu Penilaian.
    $penilaian = ['assessor.assessments.index', 'clipboard-check', 'Penilaian', ['assessor.assessments.index', 'assessor.assessments.form', 'assessor.assessments.save', 'notes.index', 'notes.create', 'notes.edit']];
    $riwayat = ['assessor.assessments.history', 'history', 'Riwayat', ['assessor.assessments.history', 'assessor.assessments.show']];

    $assessItems = [
        $penilaian,
        $riwayat,
        ['profile.show', 'user', 'Profil', ['profile.*']],
    ];

    $isStructuralReviewer = ! $u->usesDesktopLayout() && $u->isAssessor() && $u->can('assessments.review-structural');
    $pendingReviews = 0;

    if ($isStructuralReviewer) {
        $pendingReviews = app(\App\Services\ReviewRoutingService::class)->queueFor($u)->count();
    }

    $items = match (true) {
        // Kapolres / Wakapolres: kembali ke dashboard desktop-nya
        $u->usesDesktopLayout() => [
            [$u->homeRoute(), 'dashboard', 'Dashboard', [$u->homeRoute()]],
            ...$assessItems,
        ],
        $isStructuralReviewer => [
            ['assessor.home', 'home', 'Beranda', ['assessor.home']],
            $penilaian,
            ['assessor.reviews.index', 'check-circle', 'Persetujuan', ['assessor.reviews.*']],
            $riwayat,
            ['profile.show', 'user', 'Profil', ['profile.*']],
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
                $badge = $route === 'assessor.reviews.index' ? $pendingReviews : 0;
            @endphp
            <a href="{{ route($route) }}" class="{{ $active ? 'active' : '' }}">
                <span class="bp-nav-icon">
                    <x-icon :name="$icon" :size="24" :stroke="$active ? 2.1 : 1.6" />
                    @if ($badge)
                        <span class="bp-nav-badge" aria-label="{{ $badge }} menunggu">{{ $badge > 99 ? '99+' : $badge }}</span>
                    @endif
                </span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </div>
</nav>
