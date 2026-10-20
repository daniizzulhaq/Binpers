@extends('layouts.app')

@section('title', 'Kewenangan Penilai')
@section('page-title', $authorization ? 'Ubah Kewenangan' : 'Tetapkan Kewenangan Penilai')

@section('content')
    @if ($authorization)
        <div class="card border-0 shadow-sm" style="max-width: 640px">
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-4 text-muted">Penilai</dt><dd class="col-8">{{ $authorization->assessor->user->name }}</dd>
                    <dt class="col-4 text-muted">Anggota</dt><dd class="col-8">{{ $authorization->assessee->user->name }}</dd>
                </dl>
                <form method="POST" action="{{ route('hrd.authorizations.update', $authorization) }}">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-6"><x-input name="valid_from" label="Berlaku dari" type="date" :value="$authorization->valid_from?->format('Y-m-d')" /></div>
                        <div class="col-md-6"><x-input name="valid_until" label="Berlaku sampai" type="date" :value="$authorization->valid_until?->format('Y-m-d')" /></div>
                    </div>
                    <x-input name="notes" label="Catatan" :value="$authorization->notes" />
                    <x-checkbox name="is_active" label="Kewenangan aktif" :checked="$authorization->is_active" />
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                    <a href="{{ route('hrd.authorizations.index') }}" class="btn btn-outline-secondary">Batal</a>
                </form>
            </div>
        </div>
    @else
        @php($tab = old('unit_id') !== null ? 'unit' : 'select')
        @php($assessorOptions = $assessors->mapWithKeys(fn ($p) => [$p->id => $p->user->name.' ('.$p->user->roleLabel().')']))

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item"><button class="nav-link {{ $tab === 'select' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabSelect" type="button">Pilih anggota satu per satu</button></li>
                    <li class="nav-item"><button class="nav-link {{ $tab === 'unit' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabUnit" type="button">Tetapkan per unit</button></li>
                </ul>
            </div>
            <div class="card-body tab-content">
                {{-- Mode 1: pilih anggota --}}
                <div class="tab-pane fade {{ $tab === 'select' ? 'show active' : '' }}" id="tabSelect">
                    <form method="POST" action="{{ route('hrd.authorizations.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6"><x-select name="assessor_id" id="sel_assessor" label="Penilai" required :options="$assessorOptions" /></div>
                            <div class="col-md-3"><x-input name="valid_from" id="sel_from" label="Berlaku dari" type="date" /></div>
                            <div class="col-md-3"><x-input name="valid_until" id="sel_until" label="Berlaku sampai" type="date" /></div>
                        </div>

                        <label class="form-label">Anggota yang boleh dinilai <span class="text-danger">*</span></label>
                        <input type="search" id="filterAssessee" class="form-control mb-2" placeholder="Filter nama / NRP / unit">
                        <div class="border rounded p-2 mb-1 @error('assessee_ids') border-danger @enderror" style="max-height: 280px; overflow:auto" id="assesseeList">
                            @foreach ($assessees as $p)
                                <div class="form-check assessee-item" data-text="{{ strtolower($p->user->name.' '.$p->nrp.' '.($p->currentAssignment?->unit?->name)) }}">
                                    <input class="form-check-input" type="checkbox" name="assessee_ids[]" value="{{ $p->id }}" id="as{{ $p->id }}"
                                           @checked(in_array($p->id, (array) old('assessee_ids', [])))>
                                    <label class="form-check-label" for="as{{ $p->id }}">
                                        {{ $p->user->name }} <span class="text-muted small">· {{ $p->nrp }} · {{ $p->currentAssignment?->unit?->name ?? 'Tanpa unit' }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('assessee_ids')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @error('assessee_ids.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                        <x-input name="notes" id="sel_notes" label="Catatan" class="mt-2" />
                        <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                        <a href="{{ route('hrd.authorizations.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>

                {{-- Mode 2: per unit --}}
                <div class="tab-pane fade {{ $tab === 'unit' ? 'show active' : '' }}" id="tabUnit">
                    <p class="text-muted small">Seluruh anggota aktif pada unit terpilih ditetapkan kepada satu penilai. Cocok untuk penilaian lintas Bagian, Satuan, Seksi, atau Unit.</p>
                    <form method="POST" action="{{ route('hrd.authorizations.by-unit') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6"><x-select name="assessor_id" id="unit_assessor" label="Penilai" required :options="$assessorOptions" /></div>
                            <div class="col-md-6"><x-select name="unit_id" id="unit_unit" label="Unit" required :options="$units->mapWithKeys(fn ($u) => [$u->id => $u->code.' — '.$u->name])" /></div>
                        </div>
                        <x-checkbox name="include_children" id="unit_children" label="Sertakan seluruh sub-unit" :checked="true" />
                        <div class="row">
                            <div class="col-md-6"><x-input name="valid_from" id="unit_from" label="Berlaku dari" type="date" /></div>
                            <div class="col-md-6"><x-input name="valid_until" id="unit_until" label="Berlaku sampai" type="date" /></div>
                        </div>
                        <x-input name="notes" id="unit_notes" label="Catatan" />
                        <button class="btn btn-primary"><i class="bi bi-diagram-3 me-1"></i>Tetapkan per unit</button>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.getElementById('filterAssessee').addEventListener('input', function () {
                    const term = this.value.toLowerCase();
                    document.querySelectorAll('.assessee-item').forEach(function (el) {
                        el.style.display = el.dataset.text.includes(term) ? '' : 'none';
                    });
                });
            </script>
        @endpush
    @endif
@endsection
