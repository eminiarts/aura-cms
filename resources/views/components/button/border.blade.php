@props([
  'size' => 'base',
  'navigate' => true,
  'disabled' => false
])
<x-aura::button
  :navigate="$navigate"
  size="{{ $size }}"
  :disabled="$disabled"
  {{$attributes->merge([
    'class' => 'text-gray-700 bg-white ring-1 ring-gray-950/10 shadow-xs hover:bg-gray-50 active:bg-gray-100 dark:text-gray-100 dark:bg-gray-800 dark:ring-white/10 dark:hover:bg-gray-700/60 dark:active:bg-gray-700 focus-visible:ring-primary-500',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
