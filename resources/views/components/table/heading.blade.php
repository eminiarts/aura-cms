@props([
    'sortable' => null,
    'direction' => null,
    'multiColumn' => null,
])

<th
    {{ $attributes->merge(['class' => 'px-6 py-2.5 text-left'])->only('class') }}
>
    @unless ($sortable)
        <span class="text-xs font-semibold text-left text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $slot }}</span>
    @else
        <button type="button" {{ $attributes->except('class') }} class="group inline-flex items-center gap-x-1 -mx-2 px-2 py-1 rounded-md text-xs font-semibold leading-4 text-left whitespace-nowrap transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $direction ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
            <span class="table-heading">{{ $slot }}</span>

            <span class="flex items-center justify-center w-3.5 h-3.5 shrink-0" aria-hidden="true">
                @if ($direction === 'asc' || $direction === 'desc')
                    {{-- Active sort: one arrow that flips via rotation --}}
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 {{ $direction === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12.5v-9M4.5 7 8 3.5 11.5 7" /></svg>
                @else
                    {{-- Unsorted: muted arrow revealed on hover --}}
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity duration-150" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12.5v-9M4.5 7 8 3.5 11.5 7" /></svg>
                @endif
            </span>
        </button>
    @endif
</th>
