@props([
    'model',
    'field' => [],
    'capability' => [],
    'size' => 'xs',
])

<x-aura::input.wrapper placeholder="Value" :error="$model">
    <x-aura::input.datetime
        wire:model="{{ $model }}"
        :size="$size"
    />
</x-aura::input.wrapper>
