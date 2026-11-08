@props(['cols' => null])

@php
    $colSpan = match($cols) {
        'full' => 'col-span-12',
        '6' => 'col-span-12 md:col-span-6',
        '4' => 'col-span-12 md:col-span-4',
        default => ''
    };
@endphp

{{-- Same three guides as before, as quiet rows; the external-link mark shows on hover. --}}
<div {{ $attributes->merge(['class' => trim("{$colSpan} min-w-0")]) }} data-dashboard-docs>
    <h2 class="pb-2 border-b border-gray-200 aura-section-title dark:border-white/10">{{ __('Documentation') }}</h2>

    <div class="flex flex-col gap-0.5 mt-2 -mx-2">
        @foreach ([
            ['title' => __('Getting started'), 'url' => 'https://aura-cms.com/docs/'],
            ['title' => __('Resources'), 'url' => 'https://aura-cms.com/docs/resources'],
            ['title' => __('Fields'), 'url' => 'https://aura-cms.com/docs/fields'],
        ] as $link)
            <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                class="flex gap-2.5 justify-between items-center px-2 h-8 text-sm text-gray-700 rounded-md transition-colors duration-150 group hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.04] dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                <span class="truncate">{{ $link['title'] }}</span>
                <svg class="w-3.5 h-3.5 text-gray-400 opacity-0 transition-opacity duration-150 shrink-0 group-hover:opacity-100 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M7 3H4.5A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13h7a1.5 1.5 0 0 0 1.5-1.5V9M9.5 3H13v3.5M13 3 7.5 8.5" />
                </svg>
            </a>
        @endforeach
    </div>
</div>
