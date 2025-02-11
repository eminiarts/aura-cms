@props(['value'])

<label {{ $attributes->merge(['class' => 'inline-block mt-3 mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200']) }}>
    {{ $value ?? $slot }}
</label>
