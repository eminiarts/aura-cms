@props([
  'label' => '',
  'hideLabel' => false
])

<div class="flex">
    <label class="flex items-center {{ $attributes->get('disabled') ? 'cursor-not-allowed' : 'cursor-pointer' }}">
        <input {{ $attributes }}
            type="checkbox"
            class="form-checkbox block size-4 shrink-0 rounded border-0 bg-white text-primary-600 shadow-xs ring-1 ring-gray-950/15 transition duration-150 cursor-pointer hover:ring-gray-950/25 checked:bg-primary-600 checked:ring-primary-600 checked:hover:bg-primary-700 checked:hover:ring-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:bg-gray-800 dark:ring-white/20 dark:hover:ring-white/30 dark:checked:bg-primary-600 dark:checked:ring-primary-600 dark:focus-visible:ring-offset-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:ring-gray-950/10 disabled:checked:bg-gray-300 disabled:checked:ring-gray-300 dark:disabled:bg-gray-700 dark:disabled:ring-white/10 dark:disabled:checked:bg-gray-600 dark:disabled:checked:ring-gray-600"
        />
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 {{ $hideLabel ? 'sr-only' : '' }} {{ $attributes->get('disabled') ? ' opacity-50' : '' }}">
            {{ $label ?? '' }}
        </span>
    </label>
</div>
