@extends('layouts.app')

@section('title', $position ? 'Ubah Jabatan' : 'Tambah Jabatan')
@section('page-title', $position ? 'Ubah Jabatan' : 'Tambah Jabatan')

@section('content')
    <div class="card border-0 shadow-sm" style="max-width: 640px">
        <div class="card-body">
            <form method="POST" action="{{ $position ? route('hrd.positions.update', $position) : route('hrd.positions.store') }}">
                @csrf
                @if ($position) @method('PUT') @endif

                <x-input name="name" label="Nama jabatan" :value="$position?->name" required />
                <x-input name="description" label="Deskripsi" :value="$position?->description" />
                <x-checkbox name="is_active" label="Jabatan aktif" :checked="$position?->is_active ?? true" />

                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                <a href="{{ route('hrd.positions.index') }}" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
@endsection
