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
            'block w-full bg-white appearance-none text-base shadow-xs',
            'border-gray-500/30 focus:border-primary-300 focus:outline-none',
            'ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50',
            'dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700',
            'disabled:cursor-not-allowed disabled:opacity-75 disabled:bg-gray-100 dark:disabled:bg-gray-800',
            'pl-3 pr-10 py-2 rounded-lg sm:text-sm' => $size === 'default',
            'pl-2 pr-4 py-1 rounded-md text-xs' => $size === 'xs',
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
</div>
