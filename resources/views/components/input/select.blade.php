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

    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center text-gray-400 dark:text-gray-500 {{ $compact ? 'pr-1.5' : 'pr-2.5' }}" aria-hidden="true">
        <svg class="{{ $compact ? 'size-3' : 'size-4' }}" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4.25 6.25 8 10l3.75-3.75" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </span>
</div>
