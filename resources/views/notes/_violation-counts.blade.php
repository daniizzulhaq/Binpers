{{-- Rincian jumlah pelanggaran per klasifikasi; tiap baris menautkan ke filter klasifikasi tersebut. --}}
<div class="small mt-2 lh-sm">
    @foreach (\App\Enums\ViolationCategory::cases() as $vc)
        <a href="{{ route($route, ['type' => 'violation', 'category' => $vc->value]) }}"
           class="d-flex justify-content-between gap-1 text-decoration-none text-dark">
            <span class="text-muted text-truncate">{{ \Illuminate\Support\Str::after($vc->label(), 'Pelanggaran ') }}</span>
            <span class="fw-semibold">{{ (int) ($counts[$vc->value] ?? 0) }}</span>
        </a>
    @endforeach
</div>
