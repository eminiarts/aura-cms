@props([
  'size' => 'base',
  'navigate' => true
])
<x-aura::button
  :navigate="$navigate"
  size="{{ $size }}"
  {{$attributes->merge([
    'class' => 'text-primary-600 dark:text-primary-300 bg-primary-100/60 dark:bg-primary-500/10 transition-all border border-transparent hover:bg-primary-200 dark:hover:bg-primary-500/20 focus:ring-primary-300 dark:focus:ring-primary-500 shadow-none',
  ])}}
>
  @if ($icon ?? false)
    <x-slot:icon>
      {{ $icon }}
    </x-slot>
  @endif
  {{ $slot }}
</x-aura::button>
