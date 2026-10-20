@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="card shadow border-0">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="brand-badge mb-3"><i class="bi bi-shield-check"></i></div>
                <h1 class="h4 fw-bold text-primary mb-1">{{ config('app.name') }}</h1>
                <p class="text-muted small mb-0">Masuk untuk melanjutkan</p>
            </div>

            @include('partials.alerts')

            <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" id="username" name="username" value="{{ old('username') }}"
                               class="form-control @error('username') is-invalid @enderror"
                               autocomplete="username" autofocus required>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Kata sandi</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="current-password" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Tampilkan kata sandi">
                            <i class="bi bi-eye"></i>
                        </button>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>
        </div>
    </div>
    <p class="text-center text-muted small mt-3">Hanya untuk pengguna yang berwenang.</p>
@endsection

@push('scripts')
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>
@endpush
