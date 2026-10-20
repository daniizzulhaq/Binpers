@extends('layouts.mobile')

@section('title', 'Hasil '.$result['period'])
@section('page-title', 'Hasil Penilaian')

@section('content')
    @php($base = request()->routeIs('assessor.*') ? 'assessor' : 'member')

    <a href="{{ route($base.'.results.index') }}" class="small d-inline-block mb-2"><i class="bi bi-arrow-left"></i> Riwayat</a>

    <div class="card border-0 shadow-sm mb-3 text-center">
        <div class="card-body py-4">
            <div class="small text-muted">{{ $result['period'] }}</div>
            <div class="score-big my-2">{{ number_format((float) $result['final_score'], 2) }}</div>
            <span class="badge text-bg-primary fs-6">{{ $result['grade'] }}</span>
            <div class="small text-muted mt-2">Final {{ $result['finalized_at']?->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Rincian per kriteria</div>
        <ul class="list-group list-group-flush">
            @foreach ($rows as $r)
                @php($range = (float) $r['max'] - (float) $r['min'])
                @php($pct = $range > 0 ? max(0, min(100, (((float) $r['score'] - (float) $r['min']) / $range) * 100)) : 0)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span>{{ $r['criterion'] }}</span>
                        <strong>{{ $r['score'] }} <span class="text-muted small fw-normal">/ {{ $r['max'] + 0 }}</span></strong>
                    </div>
                    <div class="progress mt-1" style="height: 6px"><div class="progress-bar" style="width: {{ $pct }}%"></div></div>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Catatan dari penilai</div>
        <div class="card-body">{{ $result['member_notes'] ?: 'Tidak ada catatan.' }}</div>
    </div>
@endsection
