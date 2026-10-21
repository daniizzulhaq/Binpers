{{-- Penilaian harian yang menjadi dasar nilai rekap, dikelompokkan per kriteria. --}}
@php
    $dailyNotes = $assessment->dailyNotes();
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">
        Penilaian harian <span class="text-muted fw-normal small">({{ $dailyNotes->count() }})</span>
    </div>
    <div class="card-body p-3">
        @forelse ($dailyNotes->groupBy(fn ($n) => $n->criterionName()) as $name => $items)
            <div class="{{ $loop->first ? '' : 'mt-3' }}">
                <div class="fw-semibold small">{{ $name }}</div>
                @include('assessor.assessments._daily-notes', ['items' => $items])
            </div>
        @empty
            <div class="text-muted small">Tidak ada penilaian harian; semua kriteria memakai nilai dasar.</div>
        @endforelse
    </div>
</div>
