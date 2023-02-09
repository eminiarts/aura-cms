@props([
  'size' => 'base',
  'class' => ''
])
<x-aura::button
  size="{{ $size }}"
  {{$attributes->merge([
    'class' => $class . ' text-white bg-primary-600 border border-transparent hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-500 shadow-none',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
