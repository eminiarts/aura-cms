@props([
  'label' => '',
])

<div class="flex">
    <label class="flex items-center cursor-pointer">
        <!-- Loop through $options and display $key $value pairs -->
        <input {{ $attributes }}
            type="checkbox"
            class="block w-5 h-5 transition duration-150 ease-in-out bg-white rounded cursor-pointer border-gray-500/30 form-checkbox dark:bg-gray-700 dark:checked:bg-primary-600 dark:checked:border-primary-600 dark:border-gray-600 dark:focus:ring-gray-700 focus:ring-opacity-50 dark:focus:ring-offset-gray-900 sm:text-sm sm:leading-5 text-primary-600 focus:ring-primary-500"
        />
        <span class="ml-2 display-block">
            {{ $label ?? '' }}
        </span>
    </label>
</div>
