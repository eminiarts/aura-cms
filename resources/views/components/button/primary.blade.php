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
    'class' => 'text-white bg-primary-600 border border-transparent hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-500 shadow-none',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
