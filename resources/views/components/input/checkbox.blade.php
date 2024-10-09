@props([
  'label' => '',
  'hideLabel' => false
])

<div class="flex">
    <label class="flex items-center {{ $attributes->get('disabled') ? 'cursor-not-allowed' : 'cursor-pointer' }}">
        <!-- Loop through $options and display $key $value pairs -->
        <input {{ $attributes }}
            type="checkbox"
            class="block w-5 h-5 bg-white rounded transition duration-150 ease-in-out cursor-pointer border-gray-500/30 form-checkbox dark:bg-gray-700 dark:checked:bg-primary-600 dark:checked:border-primary-600 dark:border-gray-600 dark:focus:ring-gray-700 focus:ring-opacity-50 dark:focus:ring-offset-gray-900 sm:text-sm sm:leading-5 text-primary-600 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-200 dark:disabled:bg-gray-600"
        />
        <span class="ml-2 display-block {{ $hideLabel ? 'sr-only' : '' }} {{ $attributes->get('disabled') ? ' opacity-50' : '' }}">
            {{ $label ?? '' }}
        </span>
    </label>
</div>
