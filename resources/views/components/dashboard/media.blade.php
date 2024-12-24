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
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Media Library</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Recent uploads</p>
            </div>
            <div class="p-2 bg-rose-50 rounded-lg dark:bg-rose-900/50">
                <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="overflow-hidden relative bg-gray-100 rounded-lg group aspect-square dark:bg-gray-700">
                <div class="absolute inset-0 bg-gray-900/5"></div>
                <div class="absolute inset-0 transition-colors duration-200 group-hover:bg-gray-900/20"></div>
                <div class="absolute right-0 bottom-0 left-0 p-2 bg-gradient-to-t to-transparent from-gray-900/60">
                    <p class="text-xs text-white truncate">image-1.jpg</p>
                </div>
            </div>

            <div class="overflow-hidden relative bg-gray-100 rounded-lg group aspect-square dark:bg-gray-700">
                <div class="absolute inset-0 bg-gray-900/5"></div>
                <div class="absolute inset-0 transition-colors duration-200 group-hover:bg-gray-900/20"></div>
                <div class="absolute right-0 bottom-0 left-0 p-2 bg-gradient-to-t to-transparent from-gray-900/60">
                    <p class="text-xs text-white truncate">image-2.jpg</p>
                </div>
            </div>

            <div class="overflow-hidden relative bg-gray-100 rounded-lg group aspect-square dark:bg-gray-700">
                <div class="absolute inset-0 bg-gray-900/5"></div>
                <div class="absolute inset-0 transition-colors duration-200 group-hover:bg-gray-900/20"></div>
                <div class="absolute right-0 bottom-0 left-0 p-2 bg-gradient-to-t to-transparent from-gray-900/60">
                    <p class="text-xs text-white truncate">image-3.jpg</p>
                </div>
            </div>
        </div>

        <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
            <a href="#" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300">
                View media library →
            </a>
        </div>
    </div>
</div>
