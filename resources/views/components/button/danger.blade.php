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
    <x-aura::slot:icon>
      {{ $icon }}
    </x-aura::slot>
  @endif
  {{ $slot }}
</x-aura::button>
