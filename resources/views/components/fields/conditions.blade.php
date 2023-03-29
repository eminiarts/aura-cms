@props(['field','model'])

@php
$show = \Eminiarts\Aura\Aura::checkCondition($model, $field);
@endphp

@if ($show)
{{ $slot }}
@endif