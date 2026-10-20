@props(['icon' => 'inbox'])
<div class="text-center text-muted py-5">
    <i class="bi bi-{{ $icon }} fs-1 d-block mb-2"></i>
    {{ $slot }}
</div>
