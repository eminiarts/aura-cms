@props([
  'size' => 'base',
  'class' => ''
])
<x-aura::button
  size="{{ $size }}"
  {{$attributes->merge([
    'class' => $class . ' text-primary-600 bg-primary-100/60 dark:bg-primary-100 transition-all border border-transparent hover:bg-primary-200 focus:ring-primary-300 dark:focus:ring-primary-500 shadow-none',
  ])}}
>
  @if ($icon ?? false)
    <x-aura::slot:icon>
      {{ $icon }}
    </x-aura::slot>
  @endif
  {{ $slot }}
</x-aura::button>
