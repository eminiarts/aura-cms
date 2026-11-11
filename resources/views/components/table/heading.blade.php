@props([
    'sortable' => null,
    'direction' => null,
    'multiColumn' => null,
    'align' => 'left',
    'type' => null,
])

@php
    $alignRight = $align === 'right';
@endphp

{{-- A heading names the column and, when the field class is passed, leads with a 12px mark for its
     value type. A right-aligned column keeps the same order (mark, label) pushed to the right edge
     and puts the sort arrow in front, so the label stays flush with the numbers below it. --}}
<th
    {{ $attributes->merge(['class' => 'px-4 py-2.5 '.($alignRight ? 'text-right' : 'text-left')])->only('class') }}
>
    @unless ($sortable)
        <span class="inline-flex items-center gap-x-1.5 aura-table-header whitespace-nowrap {{ $alignRight ? 'justify-end text-right' : 'text-left' }}">
            <x-aura::table.column-icon :type="$type" />
            <span class="table-heading">{{ $slot }}</span>
        </span>
    @else
        <button type="button" {{ $attributes->except('class') }} class="group inline-flex items-center gap-x-1.5 -mx-2 px-2 py-1 rounded-md aura-table-header leading-4 whitespace-nowrap {{ $alignRight ? 'justify-end text-right' : 'text-left' }} transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $direction ? '!text-gray-900 dark:!text-white' : 'hover:text-gray-700 dark:hover:text-gray-200' }}">
            <x-aura::table.column-icon :type="$type" />
            <span class="table-heading">{{ $slot }}</span>

            <span class="flex items-center justify-center w-3.5 h-3.5 shrink-0 {{ $alignRight ? 'order-first -mr-0.5' : '-ml-0.5' }}" aria-hidden="true">
                @if ($direction === 'asc' || $direction === 'desc')
                    {{-- Active sort: one arrow that flips via rotation --}}
                    <svg class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400 transition-transform duration-200 {{ $direction === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12.5v-9M4.5 7 8 3.5 11.5 7" /></svg>
                @else
                    {{-- Unsorted: muted arrow revealed on hover --}}
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity duration-150" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12.5v-9M4.5 7 8 3.5 11.5 7" /></svg>
                @endif
            </span>
        </button>
    @endif
</th>
