@props([
  'suffix' => false,
  'prefix' => false,
  'size' => 'base',
  'class' => '',
  'label' => false,
])


<div class="w-full">
  @if ($label)
    <label class="inline-block mt-3 mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
  @endif
  <div class="flex rounded-lg">
    @if ($prefix)
      <span class="inline-flex items-center whitespace-nowrap rounded-l-lg bg-gray-50 text-gray-500 shadow-xs ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 text-sm' }}">
          {{ $prefix }}
      </span>
    @endif
    <input {{ $attributes->merge(['class' => $class . ' w-full appearance-none border-0 bg-white rounded-none text-gray-900 placeholder:text-gray-400 shadow-xs ring-1 ring-gray-950/10 transition duration-150 hover:ring-gray-950/20 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 disabled:ring-gray-950/5 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:ring-white/10 dark:hover:ring-white/20 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-500 dark:disabled:ring-white/5 [color-scheme:light] dark:[color-scheme:dark] z-[1] ' .
    ($prefix ? '' : 'rounded-l-lg ') .
    ($suffix ? '' : 'rounded-r-lg') .
    ($size === 'xs' ? ' px-2 py-1 text-xs' : ' px-3 py-2 text-sm')
    ]) }}/>

    @if ($suffix)
      <span class="inline-flex items-center whitespace-nowrap rounded-r-lg bg-gray-50 text-gray-500 shadow-xs ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 text-sm' }}">
          {{ $suffix }}
      </span>
    @endif
  </div>
</div>
