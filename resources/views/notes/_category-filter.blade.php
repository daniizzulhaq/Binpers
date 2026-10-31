{{-- Filter klasifikasi pelanggaran (otomatis hanya Riwayat Pelanggaran). --}}
<select name="category" class="form-select" aria-label="Klasifikasi pelanggaran">
    <option value="">Semua klasifikasi pelanggaran</option>
    @foreach (\App\Enums\ViolationCategory::cases() as $vc)
        <option value="{{ $vc->value }}" @selected(request('category') === $vc->value)>
            {{ $vc->label() }}
        </option>
    @endforeach
</select>
