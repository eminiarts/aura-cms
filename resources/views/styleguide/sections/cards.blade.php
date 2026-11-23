@php
    $sparklineLine = '0,22 8,20 17,23 25,17 33,19 42,14 50,16 58,11 67,13 75,9 83,12 92,7 100,9';

    $stats = [
        [
            'name' => 'Posts',
            'total' => 1284,
            'current' => 86,
            'url' => '#cards',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
            'sparkline' => ['line' => $sparklineLine, 'area' => $sparklineLine.' 100,28 0,28'],
        ],
        [
            'name' => 'Users',
            'total' => 342,
            'current' => 0,
            'url' => '#cards',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>',
            'sparkline' => ['line' => $sparklineLine, 'area' => $sparklineLine.' 100,28 0,28'],
        ],
    ];
@endphp

<div class="grid gap-4 mt-6 sm:grid-cols-2">
    <div class="aura-card">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">.aura-card</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Padding --aura-panel-padding, radius --aura-panel-radius, shadow-sm, ring-1 ring-gray-950/10 (white/10 in dark).') }}</p>
    </div>
    <div class="flex items-center aura-card-small">
        <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold text-gray-900 dark:text-white">.aura-card-small</span> — {{ __('padding --aura-panel-small-padding, radius --aura-control-radius.') }}</p>
    </div>
</div>

<div class="grid grid-cols-12 gap-4 mt-4">
    <x-aura::dashboard.stats :stats="$stats" />
</div>
<p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ __('dashboard/stats with sample data.') }}</p>

<div class="grid gap-4 mt-4 sm:grid-cols-2">
    <div class="overflow-hidden bg-white aura-radius-panel ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
        <div class="px-5 py-3 border-b border-gray-950/5 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Table card') }}</h3>
        </div>
        <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ __('The table, modals, dropdowns and toasts draw their own surface; .aura-radius-panel gives them --aura-panel-radius.') }}</p>
    </div>
    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Loading skeleton') }}</span>
        <div class="mt-3 animate-pulse">
            <div class="w-24 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
            <div class="mt-3 w-32 h-8 bg-gray-200 rounded dark:bg-gray-700"></div>
        </div>
    </div>
</div>
