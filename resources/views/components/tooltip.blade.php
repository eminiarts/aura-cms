@props([
  'text' => 'Tooltip',
  'position' => 'top'
])

@php
$positions = [
  'top' => 'top-0 left-1/2 -translate-x-1/2 translate-y-0 pb-2 group-hover:-translate-y-full ',
  'bottom' => 'bottom-0 left-1/2 -translate-x-1/2 translate-y-0 pt-2 group-hover:translate-y-full',
  'right' => 'top-1/2 right-0 translate-x-0 -translate-y-1/2 pl-2 group-hover:translate-x-full ',
  'left' => 'top-1/2 left-0 translate-x-0 -translate-y-1/2 pr-2 group-hover:-translate-x-full',
];
if (!isset($positions[$position])) {
  $position = 'top';
}
@endphp

<div class="relative group">
  <div class="z-10 pointer-events-none select-none group-hover:delay-200 opacity-0 group-hover:opacity-100 transition duration-200 absolute transform {{ optional($positions)[$position] }}">
    <div class="bg-black/50 text-white text-xs py-1 px-2 rounded whitespace-nowrap">{{ $text }}</div>
  </div>
  {{ $slot }}
</div>
