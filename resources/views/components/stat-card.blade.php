@props(['icon', 'label', 'value', 'color' => 'primary', 'sub' => null])
<div class="card border-0 shadow-sm h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <span class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi bi-{{ $icon }}"></i></span>
        <div class="lh-sm">
            <div class="text-muted small">{{ $label }}</div>
            <div class="fs-4 fw-bold">{{ $value }}</div>
            @if ($sub)<div class="small text-muted">{{ $sub }}</div>@endif
        </div>
    </div>
</div>
