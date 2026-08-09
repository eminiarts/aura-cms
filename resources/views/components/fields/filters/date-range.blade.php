@props([
    'model',
    'field' => [],
    'capability' => [],
    'size' => 'xs',
])

<div class="grid grid-cols-2 gap-2">
    <x-aura::input.wrapper :placeholder="__('From')" :error="$model.'.from'">
        <x-aura::input.date
            wire:model="{{ $model }}.from"
            :size="$size"
            :aria-label="__('From')"
        />
    </x-aura::input.wrapper>
    <x-aura::input.wrapper :placeholder="__('To')" :error="$model.'.to'">
        <x-aura::input.date
            wire:model="{{ $model }}.to"
            :size="$size"
            :aria-label="__('To')"
        />
    </x-aura::input.wrapper>
</div>
