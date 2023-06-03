@props(['field','model'])

@php
$show = app('aura')::checkCondition($model, $field);
@endphp

@if ($show)
{{ $slot }}
@endif