@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'placeholder' => '— Pilih —', 'id' => null])
@php($fid = $id ?? $name)
<div class="mb-3">
    <label for="{{ $fid }}" class="form-label">{{ $label }}@if ($required) <span class="text-danger">*</span>@endif</label>
    <select id="{{ $fid }}" name="{{ $name }}" @required($required)
            {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
