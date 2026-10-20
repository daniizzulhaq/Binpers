@extends('layouts.app')

@section('title', $period->name)
@section('page-title', $period->name)

@section('content')
    @php
        $pct = $weightTarget > 0 ? min(100, $totalWeight / $weightTarget * 100) : 0;
        $balanced = abs($totalWeight - $weightTarget) < 0.001;
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div>
                <x-status-badge :status="$period->status" class="me-2" />
                <span class="text-muted">{{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}</span>
                <span class="text-muted ms-2">· {{ $period->assessments_count }} penilaian</span>
                @if ($period->description)<div class="small text-muted mt-1">{{ $period->description }}</div>@endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hrd.periods.edit', $period) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Ubah</a>
                @if ($period->status->value === 'draft')
                    <form method="POST" action="{{ route('hrd.periods.open', $period) }}" onsubmit="return confirm('Buka periode? Kriteria akan terkunci setelah ada penilaian.')">
                        @csrf <button class="btn btn-success"><i class="bi bi-unlock me-1"></i>Buka Periode</button>
                    </form>
                @endif
                @if ($period->status->value === 'open')
                    <form method="POST" action="{{ route('hrd.periods.close', $period) }}" onsubmit="return confirm('Tutup periode? Penilai tidak dapat lagi mengisi atau mengirim.')">
                        @csrf <button class="btn btn-dark"><i class="bi bi-lock me-1"></i>Tutup Periode</button>
                    </form>
                @endif
                @if ($period->status->value === 'draft' && $period->assessments_count === 0)
                    <form method="POST" action="{{ route('hrd.periods.destroy', $period) }}" onsubmit="return confirm('Hapus periode ini?')">
                        @csrf @method('DELETE') <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Kriteria & bobot</span>
            <span class="small {{ $balanced ? 'text-success' : 'text-danger' }}">Total bobot {{ rtrim(rtrim(number_format($totalWeight, 2), '0'), '.') }} / {{ $weightTarget }}</span>
        </div>
        <div class="card-body pb-0">
            <div class="progress mb-3" style="height: 10px">
                <div class="progress-bar {{ $balanced ? 'bg-success' : 'bg-warning' }}" style="width: {{ $pct }}%"></div>
            </div>
            @if ($locked)
                <div class="alert alert-secondary py-2 small"><i class="bi bi-lock me-1"></i>Kriteria terkunci karena periode ditutup atau sudah memiliki penilaian.</div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Kriteria</th><th class="text-end">Bobot</th><th class="text-end">Skala skor</th>@unless($locked)<th></th>@endunless</tr></thead>
                <tbody>
                    @forelse ($period->criteria as $c)
                        <tr>
                            <td>{{ $c->sort_order }}</td>
                            <td><div class="fw-semibold">{{ $c->name }}</div><div class="small text-muted">{{ $c->description }}</div></td>
                            <td class="text-end">{{ $c->weight }}</td>
                            <td class="text-end">{{ $c->min_score + 0 }} – {{ $c->max_score + 0 }}</td>
                            @unless ($locked)
                                <td class="text-end text-nowrap">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCriterion{{ $c->id }}"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('hrd.criteria.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Hapus kriteria ini?')">
                                        @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            @endunless
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="list-check">Belum ada kriteria.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @unless ($locked)
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">Tambah kriteria</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hrd.criteria.store', $period) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-8"><x-input name="name" id="new_name" label="Nama kriteria" required /></div>
                                <div class="col-md-4"><x-input name="weight" id="new_weight" label="Bobot" type="number" step="0.01" required /></div>
                            </div>
                            <x-input name="description" id="new_desc" label="Deskripsi" />
                            <div class="row">
                                <div class="col-md-4"><x-input name="min_score" id="new_min" label="Skor minimum" type="number" step="0.01" value="0" required /></div>
                                <div class="col-md-4"><x-input name="max_score" id="new_max" label="Skor maksimum" type="number" step="0.01" value="100" required /></div>
                                <div class="col-md-4"><x-input name="sort_order" id="new_sort" label="Urutan" type="number" /></div>
                            </div>
                            <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
                        </form>
                    </div>
                </div>
            </div>
            @if ($period->criteria->isEmpty() && $otherPeriods->isNotEmpty())
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-semibold">Salin dari periode lain</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('hrd.criteria.copy', $period) }}">
                                @csrf
                                <x-select name="source_period_id" id="copy_src" label="Periode sumber" required :options="$otherPeriods->pluck('name', 'id')" />
                                <button class="btn btn-outline-primary w-100">Salin kriteria</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Modal ubah kriteria --}}
        @foreach ($period->criteria as $c)
            <div class="modal fade" id="editCriterion{{ $c->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('hrd.criteria.update', $c) }}" class="modal-content">
                        @csrf @method('PUT')
                        <div class="modal-header"><h2 class="modal-title h5">Ubah kriteria</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                        <div class="modal-body">
                            <div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="name" value="{{ $c->name }}" required></div>
                            <div class="mb-3"><label class="form-label">Deskripsi</label><input class="form-control" name="description" value="{{ $c->description }}"></div>
                            <div class="row g-2">
                                <div class="col-6 col-md-3"><label class="form-label">Bobot</label><input type="number" step="0.01" class="form-control" name="weight" value="{{ $c->weight }}" required></div>
                                <div class="col-6 col-md-3"><label class="form-label">Min</label><input type="number" step="0.01" class="form-control" name="min_score" value="{{ $c->min_score }}" required></div>
                                <div class="col-6 col-md-3"><label class="form-label">Maks</label><input type="number" step="0.01" class="form-control" name="max_score" value="{{ $c->max_score }}" required></div>
                                <div class="col-6 col-md-3"><label class="form-label">Urutan</label><input type="number" class="form-control" name="sort_order" value="{{ $c->sort_order }}"></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
                    </form>
                </div>
            </div>
        @endforeach
    @endunless
@endsection
