@extends('layouts.app')

@section('title', $personnel ? 'Ubah Personel' : 'Tambah Personel')
@section('page-title', $personnel ? 'Ubah Data Personel' : 'Tambah Personel & Akun')

@section('content')
    @php($edit = $personnel !== null)
    <form method="POST" action="{{ $edit ? route('hrd.personnel.update', $personnel) : route('hrd.personnel.store') }}">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Akun</div>
                    <div class="card-body">
                        <x-input name="name" label="Nama lengkap" :value="$personnel?->user->name" required />
                        <div class="row">
                            <div class="col-md-6"><x-input name="username" label="Username" :value="$personnel?->user->username" required /></div>
                            <div class="col-md-6"><x-input name="email" label="Email" type="email" :value="$personnel?->user->email" /></div>
                        </div>
                        <x-input name="password" label="Kata sandi" type="password" :required="! $edit"
                                 :help="$edit ? 'Kosongkan jika tidak diganti.' : 'Minimal 8 karakter.'" />
                        @unless ($edit)
                            <x-select name="role" label="Role" required :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])" />
                        @else
                            <div class="small text-muted">Role diubah lewat menu <a href="{{ route('hrd.users.index') }}">Akun & Role</a>.</div>
                        @endunless
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Data Personel</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><x-input name="nrp" label="NRP" :value="$personnel?->nrp" required /></div>
                            <div class="col-md-6"><x-select name="rank" label="Pangkat" :options="collect($ranks)->mapWithKeys(fn ($r) => [$r => $r])" :value="$personnel?->rank" /></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><x-select name="gender" label="Jenis kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" :value="$personnel?->gender" /></div>
                            <div class="col-md-8"><x-input name="place_of_birth" label="Tempat lahir" :value="$personnel?->place_of_birth" /></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><x-input name="birth_date" label="Tanggal lahir" type="date" :value="$personnel?->birth_date?->format('Y-m-d')" /></div>
                            <div class="col-md-6"><x-input name="join_date" label="Tanggal bergabung" type="date" :value="$personnel?->join_date?->format('Y-m-d')" /></div>
                        </div>
                        <x-input name="phone" label="Telepon" :value="$personnel?->phone" />
                        <x-textarea name="address" label="Alamat" :value="$personnel?->address" rows="2" />
                    </div>
                </div>
            </div>

            @unless ($edit)
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-semibold">Penempatan awal (opsional)</div>
                        <div class="card-body row">
                            <div class="col-md-6">
                                <x-select name="organizational_unit_id" label="Unit organisasi"
                                          :options="$units->mapWithKeys(fn ($u) => [$u->id => $u->code.' — '.$u->name])" />
                            </div>
                            <div class="col-md-6">
                                <x-select name="position_id" label="Jabatan" :options="$positions->pluck('name', 'id')" />
                            </div>
                        </div>
                    </div>
                </div>
            @endunless
        </div>

        <div class="mt-3">
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
            <a href="{{ $edit ? route('hrd.personnel.show', $personnel) : route('hrd.personnel.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
@endsection
