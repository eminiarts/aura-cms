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
            <div class="p-2 rounded-lg bg-primary-50 dark:bg-primary-900/50">
                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <button onclick="Livewire.dispatch('openModal', { component: 'aura::create-resource' })" class="flex flex-col items-center p-4 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mb-3 bg-green-50 rounded-lg dark:bg-green-900/50">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Create Resource</span>
            </button>

            <a href="{{ route('aura.settings') }}" class="flex flex-col items-center p-4 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mb-3 bg-pink-50 rounded-lg dark:bg-pink-900/50">
                    <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Settings</span>
            </a>

            <a href="{{ route('aura.team.edit', ['id' => auth()->user()->current_team_id]) }}" class="flex flex-col items-center p-4 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mb-3 bg-blue-50 rounded-lg dark:bg-blue-900/50">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Edit Team</span>
            </a>

            <a href="{{ route('aura.plugins') }}" class="flex flex-col items-center p-4 rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mb-3 bg-purple-50 rounded-lg dark:bg-purple-900/50">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Plugins</span>
            </a>
        </div>
    </div>
</div>
