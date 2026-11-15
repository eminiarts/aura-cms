@props([
  'label' => '',
  // Optional HTML picture of the option (a settings sample), shown between the radio and its label.
  'preview' => null,
])

<div class="flex">
    <label class="flex items-center cursor-pointer">
        <input {{ $attributes }}
            type="radio"
            class="form-radio block size-4 shrink-0 rounded-full border-0 bg-white text-primary-600 shadow-xs ring-1 ring-gray-400 transition duration-150 cursor-pointer hover:ring-gray-500 checked:bg-primary-600 checked:ring-primary-600 checked:hover:bg-primary-700 checked:hover:ring-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:bg-gray-950/40 dark:ring-gray-500 dark:hover:ring-gray-400 dark:checked:bg-primary-600 dark:checked:ring-primary-600 dark:focus-visible:ring-offset-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:ring-gray-950/10 disabled:checked:bg-gray-300 disabled:checked:ring-gray-300 dark:disabled:bg-gray-700 dark:disabled:ring-white/10 dark:disabled:checked:bg-gray-600 dark:disabled:checked:ring-gray-600"
        />
        @if ($preview)
            <span class="ml-3">{!! $preview !!}</span>
        @endif
        <span class="{{ $preview ? 'ml-3' : 'ml-2' }} text-sm text-gray-700 dark:text-gray-300 {{ $attributes->get('disabled') ? ' opacity-50' : '' }}">
            {{ $label ?? '' }}
        </span>
    </label>
</div>
