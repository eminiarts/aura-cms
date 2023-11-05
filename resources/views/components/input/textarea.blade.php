@props([
  'suffix' => false,
  'prefix' => false,
  'size' => 'base',
  'class' => '',
  'label' => false
])


<div class="w-full">
  @if ($label)
    <label class="inline-block mt-3 mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $label }}</label>
  @endif
  <div class="flex rounded-lg">
    @if ($prefix)
      <span class="inline-flex items-center px-3 text-gray-500 border border-r-0 rounded-l-lg border-gray-500/30 bg-gray-50 sm:text-sm dark:border-gray-700 dark:bg-gray-900">
          {{ $prefix }}
      </span>
    @endif
    <textarea {{ $attributes->merge(['class' => $class . ' shadow-xs border border-gray-500/30 appearance-none px-3 py-2 focus:outline-none w-full ring-gray-900/10 focus:ring focus:border-primary-300 focus:ring-primary-300  focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 disabled:opacity-75 disabled:bg-gray-100 disabled:dark:bg-gray-800 rounded-none bg-white dark:bg-gray-900 dark:border-gray-700 z-[1] ' . ($prefix ? '' : 'rounded-l-lg ') . ' ' . ($suffix ? '' : 'rounded-r-lg')]) }}>
    </textarea>

    @if ($suffix)
      <span class="z-[0] inline-flex items-center px-3 text-gray-500 border border-l-0 border-gray-500/30 rounded-r-lg bg-gray-50 sm:text-sm dark:border-gray-700 dark:bg-gray-900">
          {{ $suffix }}
      </span>
    @endif
  </div>
</div>
