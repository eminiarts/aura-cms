@props(['direction' => 'down'])

{{-- The one caret: the wordmark's Λ. 60 degree legs, mitered apex, flat (butt) ends, 1.5px at 16px.
     Every chevron in Aura (selects, pagers, disclosure, row and card "opens", back links) draws this
     shape; up-down is the selector mark (a menu, not an in-place toggle). Size it with a class. --}}
@php
    $paths = [
        'up' => 'M4.5 11.5 8 5.5l3.5 6',
        'down' => 'M4.5 4.5 8 10.5l3.5-6',
        'right' => 'M4.5 4.5 10.5 8l-6 3.5',
        'left' => 'M11.5 4.5 5.5 8l6 3.5',
        'up-down' => 'M5.7 6.5 8 2.5l2.3 4M5.7 9.5 8 13.5l2.3-4',
    ];
@endphp
<svg {{ $attributes->merge(['aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="butt" stroke-linejoin="miter"><path d="{{ $paths[$direction] ?? $paths['down'] }}" /></svg>
