@props(['direction' => 'down'])

{{-- The one caret: the outline chevron of the nav icon set, drawn in a 16px box at 1.5 stroke with
     round caps and joins, so at 16px it has the nav icons' line and reads as an icon, not a typed
     ">". Every chevron in Aura (selects, pagers, disclosure, row and card "opens", back links) draws
     this shape; up-down is the selector mark (a menu, not an in-place toggle). Size it with a class
     (16px where it stands alone) and color it gray-500, dark gray-400. --}}
@php
    $paths = [
        'up' => 'M4 10C4 10 6.946 6 8 6C9.054 6 12 10 12 10',
        'down' => 'M4 6C4 6 6.946 10 8 10C9.054 10 12 6 12 6',
        'right' => 'M6 4C6 4 10 6.946 10 8C10 9.054 6 12 6 12',
        'left' => 'M10 4C10 4 6 6.946 6 8C6 9.054 10 12 10 12',
        'up-down' => 'M5.33 6C5.33 6 7.3 3.33 8 3.33C8.7 3.33 10.67 6 10.67 6M5.33 10C5.33 10 7.3 12.67 8 12.67C8.7 12.67 10.67 10 10.67 10',
    ];
@endphp
<svg {{ $attributes->merge(['aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $paths[$direction] ?? $paths['down'] }}" /></svg>
