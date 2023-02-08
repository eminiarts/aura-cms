@props([
  'size' => 'base',
  'class' => 'text-gray-700 border border-gray-500/30 bg-transparent hover:bg-white focus:ring-gray-300 shadow-sm hover:border-gray-400 dark:text-gray-200 dark:border-gray-700 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:focus:ring-gray-700',
])
<x-button
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
</x-button>
