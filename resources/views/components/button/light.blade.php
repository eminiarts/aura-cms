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
    'class' => 'text-primary-700 bg-primary-600/10 hover:bg-primary-600/20 active:bg-primary-600/25 dark:text-primary-300 dark:bg-primary-400/10 dark:hover:bg-primary-400/20 dark:active:bg-primary-400/25 focus-visible:ring-primary-500',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
