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
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Common tasks and actions</p>
            </div>
            <div class="p-2 bg-purple-50 rounded-lg dark:bg-purple-900/50">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <a href="#" class="flex flex-col items-center p-4 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mb-3 bg-green-50 rounded-lg dark:bg-green-900/50">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">New Post</span>
            </a>

            <a href="#" class="flex flex-col items-center p-4 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mb-3 bg-blue-50 rounded-lg dark:bg-blue-900/50">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Upload Media</span>
            </a>

            <a href="#" class="flex flex-col items-center p-4 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mb-3 bg-yellow-50 rounded-lg dark:bg-yellow-900/50">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Edit Pages</span>
            </a>

            <a href="#" class="flex flex-col items-center p-4 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mb-3 bg-pink-50 rounded-lg dark:bg-pink-900/50">
                    <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Settings</span>
            </a>
        </div>
    </div>
</div>
