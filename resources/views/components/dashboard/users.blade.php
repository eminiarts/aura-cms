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
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Users</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Recent user activity</p>
            </div>
            <div class="p-2 bg-indigo-50 rounded-lg dark:bg-indigo-900/50">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="flex justify-center items-center w-10 h-10 bg-gray-200 rounded-full dark:bg-gray-700">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">JD</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate dark:text-white">John Doe</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Administrator</p>
                </div>
                <div class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <span class="mr-1 w-2 h-2 bg-green-400 rounded-full"></span>
                    Online
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="flex justify-center items-center w-10 h-10 bg-gray-200 rounded-full dark:bg-gray-700">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">JS</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate dark:text-white">Jane Smith</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Editor</p>
                </div>
                <div class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <span class="mr-1 w-2 h-2 bg-gray-400 rounded-full"></span>
                    Offline
                </div>
            </div>
        </div>

        <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
            <a href="#" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300">
                View all users →
            </a>
        </div>
    </div>
</div>
