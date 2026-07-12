@props(['cols' => null])

@php
    $colSpan = match($cols) {
        'full' => 'col-span-12',
        '6' => 'col-span-12 md:col-span-6',
        '4' => 'col-span-12 md:col-span-4',
        default => ''
    };
@endphp

<div {{ $attributes->merge(['class' => trim("{$colSpan} rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10")]) }}>
    <div class="p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Documentation') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Get started with our guides') }}</p>
            </div>
            <div class="p-2 rounded-lg bg-primary-50 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-900/50 dark:ring-primary-400/10">
                <svg class="size-5 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
                    <path d="M16.6127 16.0846C13.9796 17.5677 12.4773 20.6409 12 21.5V8C12.4145 7.25396 13.602 5.11646 15.6317 3.66368C16.4868 3.05167 16.9143 2.74566 17.4572 3.02468C18 3.30371 18 3.91963 18 5.15146V13.9914C18 14.6568 18 14.9895 17.8634 15.2233C17.7267 15.4571 17.3554 15.6663 16.6127 16.0846L16.6127 16.0846Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12 7.80556C11.3131 7.08403 9.32175 5.3704 5.98056 4.76958C4.2879 4.4652 3.44157 4.31301 2.72078 4.89633C2 5.47965 2 6.42688 2 8.32133V15.1297C2 16.8619 2 17.728 2.4626 18.2687C2.9252 18.8095 3.94365 18.9926 5.98056 19.3589C7.79633 19.6854 9.21344 20.2057 10.2392 20.7285C11.2484 21.2428 11.753 21.5 12 21.5C12.247 21.5 12.7516 21.2428 13.7608 20.7285C14.7866 20.2057 16.2037 19.6854 18.0194 19.3589C20.0564 18.9926 21.0748 18.8095 21.5374 18.2687C22 17.728 22 16.8619 22 15.1297V8.32133C22 6.42688 22 5.47965 21.2792 4.89633C20.5584 4.31301 19 4.76958 18 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        <div class="mt-4 -mx-3 space-y-0.5">
            @foreach ([
                ['title' => __('Getting Started'), 'description' => __('Learn the basics of Aura CMS'), 'url' => 'https://aura-cms.com/docs/'],
                ['title' => __('Resources'), 'description' => __('Create and customize resources'), 'url' => 'https://aura-cms.com/docs/resources'],
                ['title' => __('Fields'), 'description' => __('Explore available fields'), 'url' => 'https://aura-cms.com/docs/fields'],
            ] as $link)
                <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                    class="flex gap-3 items-center px-3 py-2.5 rounded-lg transition group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $link['title'] }}</p>
                        <p class="text-xs text-gray-500 truncate dark:text-gray-400">{{ $link['description'] }}</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 transition dark:text-gray-600 group-hover:text-gray-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
            @endforeach
        </div>
    </div>
</div>
