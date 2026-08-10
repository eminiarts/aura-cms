@props([
    'model',
    'field' => [],
    'capability' => [],
    'size' => 'xs',
])

<div data-package-filter="priority">
    <x-aura::fields.filters.option
        :model="$model"
        :field="$field"
        :capability="$capability"
        :size="$size"
    />
</div>
