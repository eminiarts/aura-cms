@props([
  'label' => ''
])

<div class="flex">
    <label class="flex items-center cursor-pointer">
        <input {{ $attributes }}
            type="radio"
            class="block w-4 h-4 bg-white rounded-full transition duration-150 ease-in-out cursor-pointer border-gray-500/30 focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-offset-gray-900 dark:bg-gray-700 dark:checked:bg-primary-600 dark:checked:border-primary-600 dark:border-gray-600 dark:focus:ring-gray-700 focus:checked:bg-primary-600 focus:bg-primary-600 checked:bg-primary-600 checked:hover:bg-primary-700 form-checkbox sm:text-sm sm:leading-5 disabled:opacity-50 disabled:cursor-not-allowed"
        />
        <span class="ml-2 display-block {{ $attributes->get('disabled') ? ' opacity-50' : '' }}">
            {{ $label ?? '' }}
        </span>
    </label>
</div>
