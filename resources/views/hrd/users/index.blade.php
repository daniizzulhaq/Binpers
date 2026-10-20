@extends('layouts.app')

@section('title', 'Akun & Role')
@section('page-title', 'Akun & Role')

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau username"></div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">Semua role</option>
                @foreach ($roles as $r)
                    <option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Cari</button>
            <a href="{{ route('hrd.users.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Pengguna</th><th>NRP</th><th style="min-width:200px">Role</th><th>Aktif</th><th class="text-end">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php($self = $user->is(auth()->user()))
                        <tr>
                            <td>
                                <form id="uf{{ $user->id }}" method="POST" action="{{ route('hrd.users.update', $user) }}">@csrf @method('PUT')</form>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <div class="small text-muted">{{ $user->username }}</div>
                            </td>
                            <td>{{ $user->personnel?->nrp ?? '—' }}</td>
                            <td>
                                <select name="role" form="uf{{ $user->id }}" class="form-select form-select-sm" @disabled($self)>
                                    @foreach ($roles as $r)
                                        <option value="{{ $r->value }}" @selected($user->hasRole($r->value))>{{ $r->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="is_active" value="1" form="uf{{ $user->id }}" @checked($user->is_active) @disabled($self)>
                                </div>
                            </td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-primary" form="uf{{ $user->id }}" @disabled($self)>Simpan</button>
                                <form method="POST" action="{{ route('hrd.users.reset-password', $user) }}" class="d-inline"
                                      onsubmit="return confirm('Reset kata sandi {{ $user->username }}?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary" title="Reset kata sandi"><i class="bi bi-key"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="people">Tidak ada pengguna.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
    <p class="small text-muted mt-2">Akun baru dibuat lewat menu <a href="{{ route('hrd.personnel.create') }}">Personel</a>. Akun sendiri tidak dapat dinonaktifkan atau diubah rolenya.</p>
@endsection
