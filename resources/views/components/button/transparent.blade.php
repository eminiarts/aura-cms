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
    'class' => 'text-gray-600 hover:text-gray-900 hover:bg-gray-950/5 active:bg-gray-950/10 dark:text-gray-300 dark:hover:text-white dark:hover:bg-white/10 dark:active:bg-white/20 focus-visible:ring-primary-500',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
