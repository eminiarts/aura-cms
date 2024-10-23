@props([
  'suffix' => false,
  'prefix' => false,
  'size' => 'base',
  'class' => '',
  'label' => false,
])


<div class="w-full">
  @if ($label)
    <label class="inline-block mt-3 mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $label }}</label>
  @endif
  <div class="flex rounded-lg">
    @if ($prefix)
      <span class="inline-flex items-center whitespace-nowrap bg-gray-50 rounded-l-lg border border-r-0 border-gray-500/30 dark:border-gray-700 dark:bg-gray-900 text-gray-500 {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 sm:text-sm' }}">
          {{ $prefix }}
      </span>
    @endif
    <input {{ $attributes->merge(['class' => $class . ' shadow-xs transition transition-300 border border-gray-500/30 appearance-none focus:outline-none w-full ring-gray-900/10 focus:ring focus:border-primary-300 focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 disabled:opacity-75 disabled:bg-gray-100 disabled:opacity-60 disabled:dark:bg-gray-800 rounded-none bg-white dark:bg-transparent dark:border-gray-700 dark:focus:border-gray-500 z-[1] ' .
    ($prefix ? '' : 'rounded-l-lg ') .
    ($suffix ? '' : 'rounded-r-lg') .
    ($size === 'xs' ? ' px-2 py-1 text-xs' : ' px-3 py-2 sm:text-sm')
    ]) }}/>

    @if ($suffix)
      <span class="whitespace-nowrap z-[0] inline-flex items-center border border-l-0 border-gray-500/30 rounded-r-lg bg-gray-50 dark:border-gray-700 dark:bg-gray-900 text-gray-500 {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 sm:text-sm' }}">
          {{ $suffix }}
      </span>
    @endif
  </div>
</div>
