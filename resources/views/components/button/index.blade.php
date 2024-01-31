@props([
  'permission' => false,
  'id' => null,
  'route'=> null,
  'href' => null,
  'compact' => false,
  'block' => false,
  'size' => 'base',
  'class' => 'text-white bg-primary-600 hover:bg-primary-700 focus:ring-primary-500 shadow-none',
  'type' => 'button',
])

@php

if ($block) {
  $class = 'inline-flex justify-center w-full ' . $class;
} else {
  $class = 'inline-flex ' . $class;
}

$sizes = [
  'xs' => 'px-3.5 py-1.5 text-xs font-semibold rounded-md',
  'sm' => 'px-4 py-2.5 text-sm leading-4 font-semibold rounded-lg',
  'base' => 'px-4 py-2.5 text-sm font-semibold rounded-lg',
  'lg' => 'px-5 py-3 text-base font-semibold rounded-lg',
  'xl' => 'px-7 py-3.5 text-lg font-semibold rounded-lg',
];
$iconSizes = [
  'xs' => 'w-4',
  'sm' => 'w-5',
  'base' => 'w-5',
  'lg' => 'w-6',
  'xl' => 'w-7',
];

if (!isset($sizes[$size])) {
  $size = 'base';
}
@endphp

@if(isset($href))
<a
  tabindex="0"
  href="{{ $href }}" wire:navigate
  {{$attributes->merge([
    'class' => $class . ' relative items-center focus:outline-none focus:ring-2 focus:ring-opacity-50 focus:ring-offset-2 dark:focus:ring-offset-gray-900 select-none' . ' ' .  optional($sizes)[$size],
  ])}}
>
  @if ($icon ?? false)
    <div class="absolute -ml-1">
      {{ $icon }}
    </div>
    <span class="block {{ $iconSizes[$size] }} mr-2 -ml-1"></span>
  @endif
  {{ $slot }}
</a>
@else
<button
  tabindex="0"
  type="{{ $type }}"
  {{$attributes->merge([
    'class' => $class . ' relative items-center focus:outline-none focus:ring-2 focus:ring-opacity-50 focus:ring-offset-2 dark:focus:ring-offset-gray-900 select-none ' . ' ' .  optional($sizes)[$size],
  ])}}
>
  @if ($icon ?? false)
    <div class="absolute -ml-1">
      {{ $icon }}
    </div>
    <span class="block w-6 mr-2 -ml-1"></span>
  @endif
  {{ $slot }}
</button>
@endif
