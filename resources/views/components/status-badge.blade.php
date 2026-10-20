@props(['status'])
<span {{ $attributes->class(['badge', 'text-bg-'.$status->color()]) }}>{{ $status->label() }}</span>
