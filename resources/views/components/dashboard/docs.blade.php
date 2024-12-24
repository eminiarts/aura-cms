@props(['cols'])

@php
    $colSpan = match($cols) {
        'full' => 'col-span-12',
        '6' => 'col-span-6',
        '4' => 'col-span-4',
        default => 'col-span-12'
    };
@endphp

<div {{ $attributes->merge(['class' => "{$colSpan} rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10"]) }}>
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Documentation</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started with our guides</p>
            </div>
            <div class="p-2 rounded-lg bg-primary-50 dark:bg-primary-900/50">
                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
        </div>

        <div class="-mx-4 space-y-3">
            <a href="https://aura-cms.com/docs/" class="block px-4 py-3 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="flex items-center">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Getting Started</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Learn the basics of Aura CMS</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </a>

            <a href="https://aura-cms.com/docs/resources" class="block px-4 py-3 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="flex items-center">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Resources</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Learn how to create and customize resources</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </a>

            <a href="https://aura-cms.com/docs/fields" class="block px-4 py-3 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="flex items-center">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Fields</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Explore available fields and their configuration</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </a>
        </div>
    </div>
</div>
