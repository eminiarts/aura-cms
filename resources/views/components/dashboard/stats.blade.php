@props(['stats'])

@foreach ($stats as $stat)
    @php
        $delta = $stat['delta'] ?? null;
        $deltaPercent = $stat['deltaPercent'] ?? null;
        $showSparkline = $stat['showSparkline'] ?? true;
    @endphp
    <a href="{{ $stat['url'] }}" wire:navigate
        class="flex relative flex-col col-span-12 p-5 rounded-xl ring-1 shadow-xs transition duration-150 sm:col-span-6 xl:col-span-3 bg-white dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10 hover:ring-gray-950/20 dark:hover:ring-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 group">
        <div class="flex gap-3 justify-between items-center">
            <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ $stat['name'] }}</span>
            <span class="text-gray-400 transition-colors duration-150 dark:text-gray-500 group-hover:text-primary-600 dark:group-hover:text-primary-400 [&>svg]:size-4 shrink-0" aria-hidden="true">
                {!! $stat['icon'] !!}
            </span>
        </div>

        <div class="flex gap-4 justify-between items-end mt-2">
            <div class="flex flex-wrap gap-x-2 gap-y-1 items-baseline min-w-0">
                <span class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ number_format($stat['total']) }}</span>

                {{-- Change against the previous 30 days, only when there is a previous period to compare with. --}}
                @if ($delta !== null && $delta !== 0)
                    <span class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs font-medium {{ $delta > 0 ? 'bg-green-50 text-green-700 dark:bg-green-900/40 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-400' }}"
                        title="{{ __(':current new vs :previous in the previous 30 days', ['current' => $stat['current'], 'previous' => $stat['previous']]) }}">
                        <svg class="w-3 h-3 {{ $delta < 0 ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 19V5M5 12l7-7 7 7" />
                        </svg>
                        {{ abs($deltaPercent) }}%
                    </span>
                    <span class="aura-meta">{{ __('vs previous 30 days') }}</span>
                @elseif ($delta === 0)
                    <span class="aura-meta">{{ __('Same as previous 30 days') }}</span>
                @endif
            </div>

            {{-- Sparkline in the primary color: the one place a stat card carries color. --}}
            @if ($showSparkline)
                <svg viewBox="0 0 100 30" preserveAspectRatio="none" class="mb-1 w-20 h-8 shrink-0" aria-hidden="true">
                    <polygon points="{{ $stat['sparkline']['area'] }}" class="fill-primary-500/[0.08] transition-colors duration-150 dark:fill-primary-400/10 group-hover:fill-primary-500/[0.14] dark:group-hover:fill-primary-400/[0.16]" />
                    <polyline points="{{ $stat['sparkline']['line'] }}" fill="none" vector-effect="non-scaling-stroke"
                        stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"
                        class="stroke-primary-500 dark:stroke-primary-400" />
                </svg>
            @endif
        </div>
    </a>
@endforeach
