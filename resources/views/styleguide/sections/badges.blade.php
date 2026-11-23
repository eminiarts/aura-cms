@php
    $statusPills = [
        'Draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'In Review' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
        'Scheduled' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400',
        'Published' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        'Archived' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-400',
        'Failed' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
    ];

    $navBadgeColors = [
        'primary' => 'bg-primary-100 text-primary-700',
        'gray' => 'bg-gray-100 text-gray-600',
        'red' => 'bg-red-100 text-red-700',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'green' => 'bg-green-100 text-green-700',
        'blue' => 'bg-blue-100 text-blue-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'purple' => 'bg-purple-100 text-purple-700',
        'pink' => 'bg-pink-100 text-pink-700',
    ];
@endphp

<div class="mt-6 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Status pills (fields/status-index)') }}</span>
    <div class="flex flex-wrap gap-2 mt-4">
        @foreach ($statusPills as $label => $classes)
            <span class="inline-flex items-center gap-x-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-gray-950/10 dark:ring-white/10 {{ $classes }}">
                <svg class="size-1.5 shrink-0 fill-current opacity-70" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                {{ $label }}
            </span>
        @endforeach
    </div>
</div>

<div class="mt-4 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Sidebar badges (navigation/item, light-sidebar color map)') }}</span>
    <div class="flex flex-wrap gap-2 mt-4">
        @foreach ($navBadgeColors as $color => $classes)
            <span class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 min-w-[1.25rem] text-xs font-medium tabular-nums {{ $classes }}">{{ $color }}</span>
        @endforeach
    </div>
</div>

<div class="mt-4 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Trend pills (dashboard/stats)') }}</span>
    <div class="flex flex-wrap gap-2 items-center mt-4">
        <span class="inline-flex items-center gap-0.5 rounded-full bg-green-50 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
            12%
        </span>
        <span class="inline-flex items-center gap-0.5 rounded-full bg-red-50 dark:bg-red-900/40 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400">
            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7" /></svg>
            4%
        </span>
        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">neutral</span>
    </div>
</div>
