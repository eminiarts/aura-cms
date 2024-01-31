@props(['field','model'])
@php
if($model) {
    $show = app('aura')::checkCondition($model, $field);
} else {
    $show = true;
}

@endphp

@if ($show)
{{ $slot }}
@endif
