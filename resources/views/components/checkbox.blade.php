@props(['name', 'label', 'checked' => false, 'id' => null])
@php($fid = $id ?? $name)
<div class="form-check form-switch mb-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <input class="form-check-input" type="checkbox" role="switch" id="{{ $fid }}" name="{{ $name }}" value="1"
           @checked((bool) old($name, $checked))>
    <label class="form-check-label" for="{{ $fid }}">{{ $label }}</label>
</div>
