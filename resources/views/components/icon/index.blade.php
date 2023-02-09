@props([
  'icon' => null,
  'iconClass' => null,
  'size' => 'base'
])

@php
    $sizes = [
        'xs' => 'h-4 w-4',
        'sm' => 'h-5 w-5',
        'base' => 'h-6 w-6',
        'lg' => 'h-8 w-8',
    ];

    $componentName = 'aura::icon.' . $icon;
@endphp

<div {{ $attributes->class([]) }}>
  <x-dynamic-component :component="$componentName" :class="$iconClass ?? $sizes[$size]" />
</div>
