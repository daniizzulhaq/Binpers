@extends('layouts.app')

@section('title', $personnel->user->name)
@section('page-title', 'Detail Personel')

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar-circle">{{ strtoupper(substr($personnel->user->name, 0, 1)) }}</span>
                        <div class="lh-sm">
                            <div class="fw-semibold fs-5">{{ $personnel->user->name }}</div>
                            <div class="small text-muted">{{ $personnel->user->roleLabel() }}</div>
                        </div>
                        <a href="{{ route('hrd.personnel.edit', $personnel) }}" class="btn btn-sm btn-outline-primary ms-auto"><i class="bi bi-pencil"></i></a>
                    </div>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">NRP</dt><dd class="col-7">{{ $personnel->nrp }}</dd>
                        <dt class="col-5 text-muted">Username</dt><dd class="col-7">{{ $personnel->user->username }}</dd>
                        <dt class="col-5 text-muted">Status akun</dt><dd class="col-7">{{ $personnel->user->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
                        <dt class="col-5 text-muted">Pangkat</dt><dd class="col-7">{{ $personnel->rank ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Jenis kelamin</dt><dd class="col-7">{{ ['L' => 'Laki-laki', 'P' => 'Perempuan'][$personnel->gender] ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Tempat/tgl lahir</dt><dd class="col-7">{{ $personnel->place_of_birth ?? '—' }}{{ $personnel->birth_date ? ', '.$personnel->birth_date->format('d/m/Y') : '' }}</dd>
                        <dt class="col-5 text-muted">Bergabung</dt><dd class="col-7">{{ $personnel->join_date?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Telepon</dt><dd class="col-7">{{ $personnel->phone ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Alamat</dt><dd class="col-7">{{ $personnel->address ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between">
                    <span class="fw-semibold">Poin</span><span class="badge text-bg-warning fs-6">{{ $points }}</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('hrd.points.adjust', $personnel) }}" class="row g-2">
                        @csrf
                        <div class="col-4"><input type="number" name="points" class="form-control @error('points') is-invalid @enderror" placeholder="±poin" value="{{ old('points') }}" required>
                            @error('points')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-8"><input type="text" name="description" class="form-control @error('description') is-invalid @enderror" placeholder="Alasan penyesuaian" value="{{ old('description') }}" required>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><button class="btn btn-outline-primary w-100">Catat penyesuaian</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Riwayat penempatan</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Unit</th><th>Jabatan</th><th>Mulai</th><th>Selesai</th><th>No. SK</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($personnel->assignments as $a)
                                <tr>
                                    <td>{{ $a->unit->name }}</td>
                                    <td>{{ $a->position->name }}</td>
                                    <td>{{ $a->start_date->format('d/m/Y') }}</td>
                                    <td>{!! $a->end_date ? $a->end_date->format('d/m/Y') : '<span class="badge text-bg-success">Aktif</span>' !!}</td>
                                    <td>{{ $a->decree_number ?? '—' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('hrd.assignments.destroy', $a) }}" onsubmit="return confirm('Hapus riwayat penempatan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-empty icon="geo-alt">Belum ada penempatan.</x-empty></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Penempatan / mutasi baru</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('hrd.personnel.assignments.store', $personnel) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6"><x-select name="organizational_unit_id" id="asg_unit" label="Unit" required :options="$units->mapWithKeys(fn ($u) => [$u->id => $u->code.' — '.$u->name])" /></div>
                            <div class="col-md-6"><x-select name="position_id" id="asg_pos" label="Jabatan" required :options="$positions->pluck('name', 'id')" /></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><x-input name="start_date" id="asg_start" label="Tanggal mulai" type="date" required /></div>
                            <div class="col-md-4"><x-input name="decree_number" id="asg_sk" label="Nomor SK" /></div>
                            <div class="col-md-4"><x-input name="notes" id="asg_notes" label="Catatan" /></div>
                        </div>
                        <div class="form-text mb-2">Penempatan aktif sebelumnya otomatis ditutup sehari sebelum tanggal mulai.</div>
                        <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah penempatan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
