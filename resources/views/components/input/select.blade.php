@props([
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'size' => 'base',
    'error' => null,
])

@php
    // Same sizes as buttons and inputs; 'default' is the old name of base.
    $size = $size === 'default' ? 'base' : $size;
    $size = in_array($size, ['xs', 'sm', 'base', 'lg'], true) ? $size : 'base';
    $compact = $size === 'xs';
    $invalid = $error && isset($errors) && $errors->has($error);
@endphp

<div class="relative">
    <select
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        @if ($invalid) aria-invalid="true" @endif
        {{ $attributes->class([
            // Colours, ring and states come from .aura-field (resources/css/app.css).
            'aura-field aura-control-'.$size.' block w-full appearance-none',
            'pl-3 pr-9 text-sm' => in_array($size, ['sm', 'base'], true),
            'pl-4 pr-10 text-base' => $size === 'lg',
            'pl-2 pr-7 text-xs' => $compact,
        ]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $key => $value)
            @if (is_array($value) && is_string($key))
                <optgroup label="{{ $key }}">
                    @foreach ($value as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" {{ $optionValue == $selected ? 'selected' : '' }}>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $key }}" {{ $key == $selected ? 'selected' : '' }}>
                    {{ $value }}
                </option>
            @endif
        @endforeach
    </select>

    <x-aura::input.indicator :compact="$compact" />
</div>
