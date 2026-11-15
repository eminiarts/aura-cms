@props(['direction' => 'down'])

{{-- The one caret: the wordmark's Λ. 60 degree legs, mitered apex, flat (butt) ends. At 16px it is
     8px wide and 1.6px thick, the weight of the 1.5px nav icons, so it reads as an icon, not a typed
     ">". Every chevron in Aura (selects, pagers, disclosure, row and card "opens", back links) draws
     this shape; up-down is the selector mark (a menu, not an in-place toggle). Size it with a class
     (16px where it stands alone) and color it gray-500, dark gray-400. --}}
@php
    $paths = [
        'up' => 'M4 11.46 8 4.54l4 6.92',
        'down' => 'M4 4.54 8 11.46l4-6.92',
        'right' => 'M4.54 4 11.46 8l-6.92 4',
        'left' => 'M11.46 4 4.54 8l6.92 4',
        'up-down' => 'M5.7 6.5 8 2.5l2.3 4M5.7 9.5 8 13.5l2.3-4',
    ];
@endphp
<svg {{ $attributes->merge(['aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="butt" stroke-linejoin="miter"><path d="{{ $paths[$direction] ?? $paths['down'] }}" /></svg>
