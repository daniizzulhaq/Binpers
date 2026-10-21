{{-- Judul kolom nilai: klik untuk urut tertinggi ↔ terendah. --}}
@php
    $next = $sort === 'tertinggi' ? 'terendah' : 'tertinggi';
    $icon = match ($sort) { 'tertinggi' => 'bi-sort-down', 'terendah' => 'bi-sort-up', default => 'bi-arrow-down-up' };
@endphp
<th class="text-end text-nowrap">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $next, 'page' => null]) }}"
       class="text-reset text-decoration-none" title="Urutkan {{ $next === 'tertinggi' ? 'nilai tertinggi' : 'nilai terendah' }}">
        {{ $label }} <i class="bi {{ $icon }} {{ in_array($sort, ['tertinggi', 'terendah'], true) ? 'text-primary' : 'text-muted' }}"></i>
    </a>
</th>
