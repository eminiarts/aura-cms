@props([
  'text' => 'Tooltip',
  'position' => 'top'
])

<div x-aura::data x-aura::ref="this" x-aura::init="tippy($refs.this, { content: '{{ $text }}', arrow: false, theme: 'aura', placement: '{{ $position }}', offset: [0, 8], })">
  {{ $slot }}
</div>
