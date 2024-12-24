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
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Latest updates</p>
            </div>
            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/50 p-2">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <div class="space-y-4">
            <div class="relative pl-4 border-l-2 border-primary-500 dark:border-primary-400">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">New post published</p>
                    <span class="text-xs text-gray-500 dark:text-gray-400">2m ago</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">John Doe published "Getting Started with Aura CMS"</p>
            </div>

            <div class="relative pl-4 border-l-2 border-primary-500/50 dark:border-primary-400/50">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Media uploaded</p>
                    <span class="text-xs text-gray-500 dark:text-gray-400">15m ago</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jane Smith uploaded 3 new images</p>
            </div>

            <div class="relative pl-4 border-l-2 border-primary-500/25 dark:border-primary-400/25">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Page updated</p>
                    <span class="text-xs text-gray-500 dark:text-gray-400">1h ago</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Homepage content has been updated</p>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <a href="#" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300">
                View all activity →
            </a>
        </div>
    </div>
</div>
