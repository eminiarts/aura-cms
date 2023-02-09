@props([
  'size' => 'base',
  'class' => 'text-white bg-red-600 border border-transparent hover:bg-red-700 focus:ring-red-500 shadow-none'
])
<x-aura::button
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
