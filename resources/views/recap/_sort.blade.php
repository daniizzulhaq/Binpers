{{-- Pilihan urutan daftar rekap. --}}
<select name="sort" class="form-select" aria-label="Urutkan" onchange="this.form.submit()">
    @foreach ($sorts as $key => $sortLabel)
        <option value="{{ $key }}" @selected($sort === $key)>Urut: {{ $sortLabel }}</option>
    @endforeach
</select>
