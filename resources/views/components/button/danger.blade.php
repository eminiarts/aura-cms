@props([
  'size' => 'base',
  'class' => 'text-red-500 bg-red-100 border border-transparent hover:bg-red-200 focus:ring-red-500 shadow-none'
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
