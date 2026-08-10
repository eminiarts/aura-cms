@props([
    'model',
    'field' => [],
    'capability' => [],
    'size' => 'xs',
])

<x-aura::fields.filters.option
    :model="$model"
    :field="$field"
    :capability="$capability"
    :size="$size"
/>
