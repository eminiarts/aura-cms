@props([
  'label' => ''
])

<div class="flex">
    <label class="flex items-center cursor-pointer">
        <input {{ $attributes }}
            type="radio"
            class="block transition duration-150 ease-in-out border-gray-500/30 rounded-full cursor-pointer focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-offset-gray-900 focus:checked:bg-primary-600 focus:bg-primary-600 checked:bg-primary-600 checked:hover:bg-primary-700 form-checkbox sm:text-sm sm:leading-5"
        />
        <span class="ml-2 display-block">
            {{ $label ?? '' }}
        </span>
    </label>
</div>
