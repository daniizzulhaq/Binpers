@extends('layouts.app')

@section('title', 'Poin')
@section('page-title', 'Buku Besar Poin')

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <select name="personnel_id" class="form-select">
                <option value="">Semua personel</option>
                @foreach ($personnel as $p)
                    <option value="{{ $p->id }}" @selected((int) request('personnel_id') === $p->id)>{{ $p->user->name }} ({{ $p->nrp }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="type" class="form-select">
                <option value="">Semua jenis</option>
                @foreach ($types as $t)
                    <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ route('hrd.points.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Tanggal</th><th>Personel</th><th>Jenis</th><th class="text-end">Poin</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $t)
                        <tr>
                            <td class="text-nowrap">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $t->personnel->user->name }}</td>
                            <td><span class="badge text-bg-light border">{{ $t->type->label() }}</span></td>
                            <td class="text-end fw-bold {{ $t->points < 0 ? 'text-danger' : ($t->points > 0 ? 'text-success' : '') }}">{{ $t->points > 0 ? '+' : '' }}{{ $t->points }}</td>
                            <td class="small text-muted">{{ $t->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="coin">Belum ada transaksi poin.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $transactions->links() }}</div>
@endsection
