@props([
  'suffix' => false,
  'prefix' => false,
  'size' => 'base',
  'class' => '',
  'label' => false,
  // A password input with a show/hide button at its right edge.
  'reveal' => false,
])


<div class="w-full">
  @if ($label)
    <label class="inline-block mt-3 mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
  @endif
  <div @class(['flex rounded-lg', 'relative' => $reveal]) @if ($reveal) x-data="{ shown: false }" @endif>
    @if ($prefix)
      <span class="inline-flex items-center whitespace-nowrap rounded-l-lg bg-gray-50 text-gray-500 shadow-xs ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 text-sm' }}">
          {{ $prefix }}
      </span>
    @endif
    <input {{ $attributes->merge(['class' => $class . ' w-full appearance-none border-0 bg-white rounded-none text-gray-900 placeholder:text-gray-400 shadow-xs ring-1 ring-gray-950/10 transition duration-150 hover:ring-gray-950/20 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 disabled:ring-gray-950/10 dark:bg-gray-950/40 dark:text-gray-100 dark:placeholder:text-gray-400 dark:ring-white/15 dark:hover:ring-white/25 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-500 dark:disabled:ring-white/5 [color-scheme:light] dark:[color-scheme:dark] z-[1] ' .
    ($prefix ? '' : 'rounded-l-lg ') .
    ($suffix ? '' : 'rounded-r-lg') .
    ($size === 'xs' ? ' px-2 py-1 text-xs' : ' aura-touch-control h-9 px-3 py-1.5 text-base sm:text-sm') .
    ($reveal ? ' !pr-10' : '')
    ]) }} @if ($reveal) x-bind:type="shown ? 'text' : 'password'" @endif/>

    @if ($reveal)
      <button type="button" x-on:click="shown = ! shown" x-bind:aria-pressed="shown.toString()"
              x-bind:aria-label="shown ? @js(__('Hide password')) : @js(__('Show password'))"
              aria-label="{{ __('Show password') }}"
              class="flex absolute inset-y-0 right-0 z-[2] justify-center items-center w-10 text-gray-400 rounded-r-lg transition-colors duration-150 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-500 dark:hover:text-gray-300">
        <svg x-show="! shown" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <svg x-show="shown" x-cloak class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
      </button>
    @endif

    @if ($suffix)
      <span class="inline-flex items-center whitespace-nowrap rounded-r-lg bg-gray-50 text-gray-500 shadow-xs ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 text-sm' }}">
          {{ $suffix }}
      </span>
    @endif
  </div>
</div>
