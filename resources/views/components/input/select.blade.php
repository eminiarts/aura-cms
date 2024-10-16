@props([
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

<div class="relative">
    <select
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        {{ $attributes->class([
            'block w-full pl-3 pr-10 py-2 text-base bg-white rounded-lg appearance-none shadow-xs',
            'border-gray-500/30 focus:border-primary-300 focus:outline-none',
            'ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50',
            'dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700',
            'sm:text-sm',
            'disabled:cursor-not-allowed disabled:opacity-75 disabled:bg-gray-100 dark:disabled:bg-gray-800'
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

    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
        </svg>
    </div>
</div>