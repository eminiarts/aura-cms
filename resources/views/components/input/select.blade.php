@props([
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'size' => 'default',
])

<div class="relative">
    <select
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        {{ $attributes->class([
            'block w-full appearance-none border-0 bg-white text-gray-900 shadow-xs',
            'ring-1 ring-gray-950/10 transition duration-150 hover:ring-gray-950/20',
            'focus:outline-none focus:ring-2 focus:ring-primary-500',
            'dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 dark:hover:ring-white/20',
            'disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 disabled:ring-gray-950/5',
            'dark:disabled:bg-gray-800/50 dark:disabled:text-gray-500 dark:disabled:ring-white/5',
            'pl-3 pr-9 py-2 rounded-lg text-sm' => $size === 'default',
            'pl-2 pr-7 py-1 rounded-md text-xs' => $size === 'xs',
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

    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center text-gray-400 dark:text-gray-500 {{ $size === 'xs' ? 'pr-1.5' : 'pr-2.5' }}" aria-hidden="true">
        <svg class="{{ $size === 'xs' ? 'size-3' : 'size-4' }}" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4.25 6.25 8 10l3.75-3.75" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </span>
</div>
