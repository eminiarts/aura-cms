@props([
    'model',
    'field' => [],
    'capability' => [],
    'size' => 'xs',
])

@php
    $options = collect($capability['values'] ?? [])
        ->mapWithKeys(fn ($option) => [$option['wire_value'] => $option['label']])
        ->all();
@endphp

<x-aura::input.select
    wire:model="{{ $model }}"
    :options="$options"
    :placeholder="__('Select a value')"
    :size="$size"
/>
