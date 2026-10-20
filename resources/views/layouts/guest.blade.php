<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="auth-bg">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="auth-card w-100">
            @yield('content')
        </div>
    </main>
    @stack('scripts')
</body>
</html>