@extends('layouts.app')

@section('title', $period ? 'Ubah Periode' : 'Buat Periode')
@section('page-title', $period ? 'Ubah Periode' : 'Buat Periode Penilaian')

@section('content')
    <div class="card border-0 shadow-sm" style="max-width: 640px">
        <div class="card-body">
            <form method="POST" action="{{ $period ? route('hrd.periods.update', $period) : route('hrd.periods.store') }}">
                @csrf
                @if ($period) @method('PUT') @endif

                <x-input name="name" label="Nama periode" :value="$period?->name" required help="Contoh: Triwulan 4 2026" />
                <div class="row">
                    <div class="col-md-6"><x-input name="start_date" label="Tanggal mulai" type="date" :value="$period?->start_date?->format('Y-m-d')" required /></div>
                    <div class="col-md-6"><x-input name="end_date" label="Tanggal selesai" type="date" :value="$period?->end_date?->format('Y-m-d')" required /></div>
                </div>
                <x-textarea name="description" label="Deskripsi" :value="$period?->description" rows="2" />

                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                <a href="{{ $period ? route('hrd.periods.show', $period) : route('hrd.periods.index') }}" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
@endsection
