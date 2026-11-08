@props(['cols' => null])

@php
    $colSpan = match($cols) {
        'full' => 'col-span-12',
        '6' => 'col-span-12 md:col-span-6',
        '4' => 'col-span-12 md:col-span-4',
        default => ''
    };
@endphp

<div {{ $attributes->merge(['class' => trim("{$colSpan} rounded-xl bg-white dark:bg-gray-800 shadow-xs ring-1 ring-gray-950/10 dark:ring-white/10")]) }}>
    <div class="p-6">
        <div>
            <h2 class="aura-section-title">{{ __('Documentation') }}</h2>
            <p class="mt-0.5 aura-meta">{{ __('Get started with our guides') }}</p>
        </div>

        <div class="mt-4 -mx-3 space-y-0.5">
            @foreach ([
                ['title' => __('Getting Started'), 'description' => __('Learn the basics of Aura CMS'), 'url' => 'https://aura-cms.com/docs/'],
                ['title' => __('Resources'), 'description' => __('Create and customize resources'), 'url' => 'https://aura-cms.com/docs/resources'],
                ['title' => __('Fields'), 'description' => __('Explore available fields'), 'url' => 'https://aura-cms.com/docs/fields'],
            ] as $link)
                <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                    class="flex gap-3 items-center px-3 py-2 rounded-lg transition-colors duration-150 group hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $link['title'] }}</p>
                        <p class="aura-meta truncate">{{ $link['description'] }}</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 transition-colors duration-150 dark:text-gray-600 group-hover:text-gray-500 dark:group-hover:text-gray-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
            @endforeach
        </div>
    </div>
</div>
