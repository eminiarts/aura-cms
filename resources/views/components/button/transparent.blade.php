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
    'class' => 'text-gray-500 dark:text-gray-200 bg-transparent border border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-gray-200',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
