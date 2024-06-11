@props([
  'size' => 'base',
  'navigate' => true
])
<x-aura::button
  :navigate="$navigate"
  size="{{ $size }}"
  {{$attributes->merge([
    'class' => 'text-white bg-red-600 border border-transparent hover:bg-red-700 focus:ring-red-500 shadow-none',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
