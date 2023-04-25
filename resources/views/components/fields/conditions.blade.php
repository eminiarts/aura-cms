@props(['field','model'])

@php
$show = app('aura')::checkCondition($model, $field['slug']);
@endphp

@if ($show)
{{ $slot }}
@endif