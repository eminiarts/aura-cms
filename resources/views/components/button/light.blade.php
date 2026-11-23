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
    'class' => 'aura-button-light text-primary-700 bg-primary-600/10 hover:bg-primary-600/20 active:bg-primary-600/25 focus-visible:ring-primary-500',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
