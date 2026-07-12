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
    'class' => 'text-red-700 bg-red-500/10 hover:bg-red-500/20 active:bg-red-500/25 dark:text-red-400 dark:bg-red-500/10 dark:hover:bg-red-500/20 dark:active:bg-red-500/25 focus-visible:ring-red-500',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
