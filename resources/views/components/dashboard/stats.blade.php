@props(['stats'])

@foreach ($stats as $stat)
    <a href="{{ $stat['url'] }}" wire:navigate
        class="flex overflow-hidden relative flex-col col-span-12 rounded-lg ring-1 shadow-sm transition duration-150 sm:col-span-6 xl:col-span-3 bg-white dark:bg-gray-800 ring-gray-950/5 dark:ring-white/10 hover:ring-gray-950/10 dark:hover:ring-white/20 group">
        <div class="flex-1 p-5 pb-0">
            <div class="flex gap-3 items-center">
                <div class="p-2 rounded-lg text-gray-500 bg-gray-100 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 [&>svg]:w-5 [&>svg]:h-5 shrink-0">
                    {!! $stat['icon'] !!}
                </div>
                <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ $stat['name'] }}</span>
            </div>

            <div class="flex gap-2 items-baseline mt-4">
                <span class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ number_format($stat['total']) }}</span>

                @if ($stat['current'] > 0)
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-green-50 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 19V5M5 12l7-7 7 7" />
                        </svg>
                        {{ number_format($stat['current']) }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('last 30 days') }}</span>
                @else
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('No new entries in 30 days') }}</span>
                @endif
            </div>
        </div>

        <svg viewBox="0 0 100 28" preserveAspectRatio="none" class="mt-4 w-full h-9" aria-hidden="true">
            <polygon points="{{ $stat['sparkline']['area'] }}" class="fill-gray-100/50 transition-colors duration-150 dark:fill-white/[0.02] group-hover:fill-gray-100 dark:group-hover:fill-white/[0.04]" />
            <polyline points="{{ $stat['sparkline']['line'] }}" fill="none" vector-effect="non-scaling-stroke"
                stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"
                class="stroke-gray-300 dark:stroke-gray-600 group-hover:stroke-gray-400 dark:group-hover:stroke-gray-500" />
        </svg>
    </a>
@endforeach
