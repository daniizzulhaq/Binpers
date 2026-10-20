<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @php($unread = auth()->user()->unreadNotifications()->count())

    <aside class="app-sidebar d-none d-lg-flex flex-column">
        @include('partials.sidebar')
    </aside>

    <div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="sidebarMobile" aria-label="Menu">
        <div class="offcanvas-header justify-content-end py-2">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('partials.sidebar')
        </div>
    </div>

    <div class="app-main">
        <header class="app-topbar px-3 px-lg-4 py-2 d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="h5 mb-0 fw-semibold text-primary">@yield('page-title', 'Dashboard')</h1>

            <a href="{{ route('notifications.index') }}" class="btn btn-light border position-relative ms-auto" aria-label="Notifikasi">
                <i class="bi bi-bell"></i>
                @if ($unread)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $unread > 99 ? '99+' : $unread }}</span>
                @endif
            </a>

            <div class="dropdown">
                <button class="btn btn-light border d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-3 py-2 small text-muted">{{ auth()->user()->roleLabel() }}</li>
                    <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>Keluar
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="p-3 p-lg-4 flex-grow-1">
            @include('partials.alerts')
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
