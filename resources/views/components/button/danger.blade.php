@props([
  'size' => 'base',
  'class' => 'text-red-500 bg-red-100 dark:bg-gray-800 dark:text-red-500 dark:border-transparent dark:hover:bg-red border border-transparent hover:bg-red-500/30 focus:ring-red-500 shadow-none',
  'navigate' => true
])
<x-aura::button
  :navigate="$navigate"
  size="{{ $size }}"
  {{$attributes->merge([
    'class' => $class . ' ',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
