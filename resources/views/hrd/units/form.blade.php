@extends('layouts.app')

@section('title', $unit ? 'Ubah Unit' : 'Tambah Unit')
@section('page-title', $unit ? 'Ubah Unit Organisasi' : 'Tambah Unit Organisasi')

@section('content')
    <div class="card border-0 shadow-sm" style="max-width: 720px">
        <div class="card-body">
            <form method="POST" action="{{ $unit ? route('hrd.units.update', $unit) : route('hrd.units.store') }}">
                @csrf
                @if ($unit) @method('PUT') @endif

                <div class="row">
                    <div class="col-md-4"><x-input name="code" label="Kode" :value="$unit?->code" required help="Huruf, angka, strip, garis bawah." /></div>
                    <div class="col-md-8"><x-input name="name" label="Nama unit" :value="$unit?->name" required /></div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-select name="type" label="Jenis" :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" :value="$unit?->type?->value" required />
                    </div>
                    <div class="col-md-6">
                        <x-select name="parent_id" label="Induk" placeholder="— Tanpa induk —"
                                  :options="$parents->mapWithKeys(fn ($p) => [$p->id => $p->code.' — '.$p->name])" :value="$unit?->parent_id" />
                    </div>
                </div>
                <x-checkbox name="is_active" label="Unit aktif" :checked="$unit?->is_active ?? true" />

                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                <a href="{{ route('hrd.units.index') }}" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
@endsection
