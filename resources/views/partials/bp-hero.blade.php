@php
    $person = $person ?? null;
    $person?->loadMissing(['currentAssignment.unit', 'currentAssignment.position']);
    $cur = $person?->currentAssignment;
    $hour = (int) now()->format('G');
    $greet = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
@endphp

<section class="bp-hero">
    <div class="bp-hero-star"><x-icon name="badge" :size="104" :stroke="1" /></div>
    <div class="bp-hero-greet">{{ $greet }},</div>
    <div class="bp-hero-name">{{ auth()->user()->name }}</div>
    <div class="bp-hero-meta">
        {{ $person?->rank ?: auth()->user()->roleLabel() }}@if ($person) · NRP {{ $person->nrp }}@endif
    </div>
    @if ($cur)
        <div class="bp-chip">
            <x-icon name="org" :size="14" />
            <span class="text-truncate">{{ $cur->position->name }} · {{ $cur->unit->name }}</span>
        </div>
    @endif
</section>
