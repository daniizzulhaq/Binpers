<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b2a5b">
    <title>@yield('title', 'Beranda') — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('styles')

    <style>
        :root {
            --bp-navy: #0b2a5b;
            --bp-navy-dark: #071b3d;
            --bp-gold: #c9a227;
            --bp-bg: #f2f4f8;
            --bp-width: 450px;
        }
        body.bp-body { background: #e4e8ef; }

        /* Bingkai selebar HP, di tengah layar */
        .bp-shell {
            position: relative; min-height: 100vh;
            max-width: var(--bp-width); margin: 0 auto;
            background: var(--bp-bg);
            box-shadow: 0 0 28px rgba(7,27,61,.16);
        }

        /* Layar lebar: scroll di tepi bingkai, bukan di tepi jendela */
        @media (min-width: 451px) {
            html, body.bp-body { height: 100%; overflow: hidden; }
            .bp-shell {
                height: 100vh; overflow-y: auto; overscroll-behavior: contain;
                scrollbar-width: thin; scrollbar-color: #b7c0cf transparent;
            }
            .bp-shell::-webkit-scrollbar { width: 6px; }
            .bp-shell::-webkit-scrollbar-track { background: transparent; }
            .bp-shell::-webkit-scrollbar-thumb { background: #b7c0cf; border-radius: 3px; }
            .bp-shell::-webkit-scrollbar-thumb:hover { background: #8f9bb0; }
        }

        /* HP: tetap bisa scroll, scrollbar disembunyikan */
        @media (max-width: 450px) {
            html, body.bp-body { scrollbar-width: none; }
            html::-webkit-scrollbar, body.bp-body::-webkit-scrollbar { display: none; }
        }

        .bp-i { display: inline-block; vertical-align: -.125em; flex-shrink: 0; }

        /* ---------- Header ---------- */
        .bp-topbar {
            position: sticky; top: 0; z-index: 1030; color: #fff;
            background: linear-gradient(135deg, var(--bp-navy-dark), var(--bp-navy));
            border-bottom: 3px solid var(--bp-gold);
            padding-top: env(safe-area-inset-top);
        }
        .bp-topbar-inner {
            padding: .6rem 1rem;
            display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        }
        .bp-brand { display: flex; align-items: center; gap: .65rem; color: #fff; text-decoration: none; min-width: 0; }
        .bp-brand:hover { color: #fff; }
        .bp-logo {
            position: relative; width: 42px; height: 42px; flex-shrink: 0; overflow: hidden;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 12px; font-size: 1.45rem; color: var(--bp-gold);
            background: rgba(255,255,255,.1); border: 1px solid rgba(201,162,39,.65);
        }
        .bp-logo img { width: 100%; height: 100%; object-fit: contain; padding: 3px; }
        /* Logo gambar: tanpa kotak bingkai agar lambang terlihat penuh */
        .bp-logo:has(img) { width: 46px; height: 46px; background: none; border: 0; border-radius: 0; }
        .bp-logo:has(img) img { padding: 0; }
        .bp-logo-star {
            position: absolute; top: 50%; left: 50%; font-size: .55rem;
            color: var(--bp-navy-dark); transform: translate(-50%, -58%);
        }
        .bp-brand-text { display: flex; flex-direction: column; line-height: 1.15; min-width: 0; }
        .bp-brand-text strong { font-size: 1rem; letter-spacing: .09em; text-transform: uppercase; }
        .bp-brand-text small { font-size: .68rem; opacity: .75; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .bp-bell {
            position: relative; width: 40px; height: 40px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 12px; font-size: 1.25rem; color: #fff; text-decoration: none;
            background: rgba(255,255,255,.1);
        }
        .bp-bell:hover { color: #fff; background: rgba(255,255,255,.18); }
        .bp-dot {
            position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; padding: 0 4px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 9px; font-size: .65rem; font-weight: 700; color: #fff;
            background: #dc3545; border: 2px solid var(--bp-navy);
        }

        /* ---------- Konten ---------- */
        .bp-content { padding-bottom: 6.5rem; }

        .bp-hero {
            position: relative; overflow: hidden; color: #fff;
            border-radius: 1rem; padding: 1.1rem 1.15rem; margin-bottom: 1rem;
            background: linear-gradient(135deg, #071b3d 0%, #0b2a5b 60%, #16418a 100%);
            border-left: 5px solid var(--bp-gold);
            box-shadow: 0 .5rem 1rem rgba(7,27,61,.18);
        }
        .bp-hero::after {
            content: ""; position: absolute; inset: 0; pointer-events: none;
            background: repeating-linear-gradient(135deg, rgba(255,255,255,.035) 0 12px, transparent 12px 24px);
        }
        .bp-hero > *:not(.bp-hero-star) { position: relative; z-index: 1; }
        .bp-hero-star { position: absolute; right: -.2rem; top: -.5rem; font-size: 5.5rem; line-height: 1; color: rgba(201,162,39,.18); }
        .bp-hero-greet { font-size: .8rem; opacity: .75; }
        .bp-hero-name { font-size: 1.3rem; font-weight: 700; line-height: 1.2; word-break: break-word; }
        .bp-hero-meta { font-size: .8rem; opacity: .8; margin-top: .15rem; }
        .bp-chip {
            display: inline-flex; align-items: center; gap: .35rem; max-width: 100%;
            margin-top: .6rem; padding: .25rem .75rem; border-radius: 999px; font-size: .78rem;
            background: rgba(255,255,255,.14); border: 1px solid rgba(201,162,39,.45);
        }

        .bp-card { background: #fff; border: 0; border-radius: 1rem; box-shadow: 0 1px 3px rgba(16,24,40,.08); }
        .bp-title {
            display: flex; align-items: center; gap: .45rem; margin-bottom: .6rem;
            font-size: .75rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--bp-navy);
        }
        .bp-title::before { content: ""; width: 4px; height: 14px; border-radius: 2px; background: var(--bp-gold); }
        .bp-stat { text-align: center; padding: .8rem .25rem; }
        .bp-stat .n { font-size: 1.5rem; font-weight: 700; line-height: 1; color: var(--bp-navy); }
        .bp-stat .l { margin-top: .3rem; font-size: .68rem; line-height: 1.2; color: #6b7685; }
        .bp-action {
            display: flex; align-items: center; gap: .6rem; padding: .85rem 1rem; border-radius: 1rem;
            text-decoration: none; font-weight: 600; line-height: 1.2;
        }
        .bp-action-primary { background: var(--bp-navy); color: #fff; }
        .bp-action-primary:hover { color: #fff; background: var(--bp-navy-dark); }
        .bp-action-gold { background: #fff; color: var(--bp-navy); border: 2px solid var(--bp-gold); }
        .bp-action-gold:hover { color: var(--bp-navy); background: #fffaf0; }
        .bp-action i { font-size: 1.4rem; }

        /* ---------- Navigasi bawah (selebar bingkai) ---------- */
        .bp-nav {
            position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: var(--bp-width); z-index: 1030; background: #fff;
            border-top: 1px solid #dfe3ea; box-shadow: 0 -4px 14px rgba(7,27,61,.08);
            padding-bottom: env(safe-area-inset-bottom);
        }
        .bp-nav-inner { display: flex; }
        .bp-nav a {
            position: relative; flex: 1; display: flex; flex-direction: column; align-items: center; gap: .1rem;
            padding: .6rem 0 .45rem; font-size: .68rem; color: #6b7685; text-decoration: none;
        }
        .bp-nav a i { font-size: 1.3rem; }
        .bp-nav a.active { color: var(--bp-navy); font-weight: 600; }
        .bp-nav-icon { position: relative; display: inline-flex; }
        .bp-nav-badge {
            position: absolute; top: -5px; right: -10px; min-width: 17px; height: 17px; padding: 0 4px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 9px; font-size: .62rem; font-weight: 700; line-height: 1; color: #fff;
            background: #dc3545; border: 2px solid #fff;
        }
        .bp-nav a.active::before {
            content: ""; position: absolute; top: 0; left: 28%; right: 28%; height: 3px;
            border-radius: 0 0 3px 3px; background: var(--bp-gold);
        }
    </style>
</head>
<body class="bp-body">
    @php
        $bpUser = auth()->user();
        $bpUnread = $bpUser->unreadNotifications()->count();
        $bpHome = $bpUser->homeRoute() ?? 'profile.show';
        $bpLogo = file_exists(public_path('images/logo-mark.png'));
    @endphp

    <div class="bp-shell">
        <header class="bp-topbar">
            <div class="bp-topbar-inner">
                <a href="{{ route($bpHome) }}" class="bp-brand">
                     <span class="bp-logo">
                        @if ($bpLogo)
                            <img src="{{ asset('images/logo-mark.png') }}" alt="Logo Binpers">
                        @else
                            <x-icon name="badge" :size="28" :stroke="1.6" />
                        @endif
                    </span>
                    <span class="bp-brand-text">
                        <strong>{{ config('app.name') }}</strong>
                        <small>Penilaian Kinerja Personel</small>
                    </span>
                </a>

                <a href="{{ route('notifications.index') }}" class="bp-bell" aria-label="Notifikasi">
                    <x-icon name="bell" :size="22" />
                    @if ($bpUnread)
                        <span class="bp-dot">{{ $bpUnread > 99 ? '99+' : $bpUnread }}</span>
                    @endif
                </a>
            </div>
        </header>

        <main class="bp-content px-3 pt-3">
            @include('partials.alerts')
            @yield('content')
        </main>
    </div>

    @include('partials.mobile-nav')

    @stack('scripts')
</body>
</html>
