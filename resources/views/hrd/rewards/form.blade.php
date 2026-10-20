@extends('layouts.app')

@section('title', $reward ? 'Ubah Apresiasi' : 'Tambah Apresiasi')
@section('page-title', $reward ? 'Ubah Apresiasi' : 'Tambah Apresiasi')

@section('content')
    <div class="card border-0 shadow-sm" style="max-width: 640px">
        <div class="card-body">
            <form method="POST" action="{{ $reward ? route('hrd.rewards.update', $reward) : route('hrd.rewards.store') }}">
                @csrf
                @if ($reward) @method('PUT') @endif

                <x-input name="name" label="Nama apresiasi" :value="$reward?->name" required />
                <x-textarea name="description" label="Deskripsi" :value="$reward?->description" rows="2" />
                <x-input name="min_points" label="Syarat poin minimum" type="number" :value="$reward?->min_points" required />
                <x-checkbox name="is_active" label="Apresiasi aktif" :checked="$reward?->is_active ?? true" />

                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                <a href="{{ route('hrd.rewards.index') }}" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
@endsection
