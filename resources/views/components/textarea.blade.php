@props(['name', 'label', 'value' => null, 'required' => false, 'rows' => 3, 'help' => null, 'id' => null])
@php($fid = $id ?? $name)
<div class="mb-3">
    <label for="{{ $fid }}" class="form-label">{{ $label }}@if ($required) <span class="text-danger">*</span>@endif</label>
    <textarea id="{{ $fid }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
