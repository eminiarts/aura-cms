@props([
  'permission' => false,
  'id' => null,
  'route'=> null,
  'href' => null,
  'compact' => false,
  'block' => false,
  'size' => 'base',
  'class' => 'text-white bg-primary-600 shadow-button hover:bg-primary-500 active:bg-primary-700 focus-visible:ring-primary-500',
  'type' => 'button',
  'navigate' => true,
  'disabled' => false,
])

@php

if ($block) {
  $class = 'flex w-full' . ' ' . $class;
} else {
  $class = 'inline-flex' . ' ' . $class;
}

// Fixed heights so buttons, inputs and chips align on one baseline:
// xs 28px, sm 32px, base 36px, lg 40px, xl 48px.
$sizes = [
  'icon-xs' => 'h-7 w-7 p-0 text-xs font-medium rounded-md',
  'xs' => 'h-7 gap-1.5 px-2.5 text-xs font-medium rounded-md',
  'sm' => 'h-8 gap-1.5 px-3 text-sm font-medium rounded-lg',
  'base' => 'h-9 gap-2 px-3.5 text-sm font-medium rounded-lg',
  'lg' => 'h-10 gap-2 px-4 text-sm font-medium rounded-lg',
  'xl' => 'h-12 gap-2.5 px-5 text-base font-medium rounded-xl',
];
$iconSizes = [
  'icon-xs' => 'size-3.5',
  'xs' => 'size-3.5',
  'sm' => 'size-4',
  'base' => 'size-4',
  'lg' => 'size-4',
  'xl' => 'size-5',
];

if (!isset($sizes[$size])) {
  $size = 'base';
}

$disabledClass = $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
$class .= ' ' . $disabledClass;

$sharedClass = ' aura-button relative items-center justify-center whitespace-nowrap select-none transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 ';

@endphp


@if(isset($href))
<a
  @if(isset($id)) id="{{ $id }}" @endif
  tabindex="{{ $disabled ? '-1' : '0' }}"
  href="{{ $disabled ? '#' : $href }}"
  @if($navigate && !$disabled) wire:navigate @endif
  {{$attributes->merge([
    'class' => $class . $sharedClass . $sizes[$size],
  ])}}
  @if($disabled) aria-disabled="true" @endif
>
  @if ($icon ?? false)
    <span class="inline-flex items-center justify-center shrink-0 -ml-0.5 [&_svg]:size-full {{ $iconSizes[$size] }}">
      {{ $icon }}
    </span>
  @endif
  {{ $slot }}
</a>
@else
<button
  @if(isset($id)) id="{{ $id }}" @endif
  tabindex="{{ $disabled ? '-1' : ($attributes->has('tabindex') ? $attributes->get('tabindex') : '0') }}"
  type="{{ $type }}"
  {{$attributes->merge([
    'class' => $class . $sharedClass . $sizes[$size],
  ])}}
  @if($disabled) disabled aria-disabled="true" @endif
>
  @if ($icon ?? false)
    <span class="inline-flex items-center justify-center shrink-0 -ml-0.5 [&_svg]:size-full {{ $iconSizes[$size] }}">
      {{ $icon }}
    </span>
  @endif
  {{ $slot }}
</button>
@endif
