@props([
  'suffix' => false,
  'prefix' => false,
  'size' => 'base',
  'class' => '',
  'label' => false,
])

@php
  // Same sizes as buttons: the height comes from .aura-control-{size} (--aura-control-height-*),
  // the radius from .aura-field (--aura-control-radius). Sizes set padding-x and text only.
  $size = in_array($size, ['xs', 'sm', 'base', 'lg'], true) ? $size : 'base';
  $sizeClass = [
    'xs' => 'aura-control-xs px-2 text-xs',
    'sm' => 'aura-control-sm px-3 text-sm',
    'base' => 'aura-control-base px-3 text-sm',
    'lg' => 'aura-control-lg px-4 text-base',
  ][$size];
  $addonClass = [
    'xs' => 'px-2 text-xs',
    'sm' => 'px-3 text-sm',
    'base' => 'px-3 text-sm',
    'lg' => 'px-4 text-base',
  ][$size];
@endphp

<div class="w-full">
  @if ($label)
    <label class="inline-block mt-3 mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
  @endif
  <div class="flex aura-field-group">
    @if ($prefix)
      <span class="aura-field inline-flex items-center whitespace-nowrap bg-gray-50 text-gray-500 shadow-xs ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 {{ $addonClass }}">
          {{ $prefix }}
      </span>
    @endif
    <input {{ $attributes->merge(['class' => $class . ' aura-field w-full appearance-none border-0 bg-white text-gray-900 placeholder:text-gray-400 shadow-xs ring-1 ring-gray-950/10 transition duration-150 hover:ring-gray-950/20 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 disabled:ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:ring-white/10 dark:hover:ring-white/20 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-500 dark:disabled:ring-white/5 [color-scheme:light] dark:[color-scheme:dark] z-[1] ' . $sizeClass
    ]) }}/>

    @if ($suffix)
      <span class="aura-field inline-flex items-center whitespace-nowrap bg-gray-50 text-gray-500 shadow-xs ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 {{ $addonClass }}">
          {{ $suffix }}
      </span>
    @endif
  </div>
</div>
