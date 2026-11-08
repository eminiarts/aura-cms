@props(['stats', 'calm' => false])

{{-- Label + icon, value, then only what the data supports: a delta row when there is a previous
     period to compare with, a sparkline when the series has enough points. Cards share the grid
     row height, so a card without those rows keeps its bottom open instead of showing a dash and
     an empty chart. Calm cards (an empty workspace) keep only label and a muted value.
     Phones: a 2x2 grid with number and delta; the sparkline is a desktop detail. --}}
@foreach ($stats as $stat)
    @php
        $delta = $calm ? null : ($stat['delta'] ?? null);
        $deltaPercent = $stat['deltaPercent'] ?? null;
        $showSparkline = ! $calm && ($stat['showSparkline'] ?? true) && ! empty($stat['sparkline']['line']);
    @endphp
    <a href="{{ $stat['url'] }}" wire:navigate
        class="flex relative flex-col col-span-6 p-4 rounded-xl ring-1 shadow-xs transition duration-150 sm:p-5 xl:col-span-3 bg-white dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10 hover:ring-gray-950/20 dark:hover:ring-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 group">
        <div class="flex gap-3 justify-between items-center">
            <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ $stat['name'] }}</span>
            <span class="hidden text-gray-400 transition-colors duration-150 sm:block dark:text-gray-500 group-hover:text-primary-600 dark:group-hover:text-primary-400 [&>svg]:size-4 shrink-0" aria-hidden="true">
                {!! $stat['icon'] !!}
            </span>
        </div>

        <span class="mt-1.5 text-2xl font-semibold tracking-tight sm:mt-2 sm:text-3xl {{ $calm && ! $stat['total'] ? 'text-gray-300 dark:text-gray-600' : 'text-gray-900 dark:text-white' }}">{{ number_format($stat['total']) }}</span>

        @if ($delta !== null)
            {{-- Change against the previous 30 days. --}}
            <div class="flex gap-2 items-center mt-1.5 min-w-0">
                <span class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs font-medium {{ $delta > 0 ? 'bg-green-50 text-green-700 dark:bg-green-900/40 dark:text-green-400' : ($delta < 0 ? 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-400' : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300') }}"
                    title="{{ __(':current new vs :previous in the previous 30 days', ['current' => $stat['current'], 'previous' => $stat['previous']]) }}">
                    @if ($delta !== 0)
                        <svg class="w-3 h-3 {{ $delta < 0 ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 19V5M5 12l7-7 7 7" />
                        </svg>
                    @endif
                    {{ abs((int) $deltaPercent) }}%
                </span>
                <span class="hidden truncate sm:inline aura-meta">{{ __('vs previous 30 days') }}</span>
            </div>
        @endif

        @if ($showSparkline)
            {{-- One sparkline style everywhere: 1.5px primary line over a 10% primary fill, 32px tall,
                 sitting on the bottom padding. The one place a stat card carries color. --}}
            <div class="hidden pt-3 mt-auto sm:block">
                <svg viewBox="0 0 100 30" preserveAspectRatio="none" class="w-full h-8" aria-hidden="true">
                    <polygon points="{{ $stat['sparkline']['area'] }}" class="fill-primary-500/10 dark:fill-primary-400/10" />
                    <polyline points="{{ $stat['sparkline']['line'] }}" fill="none" vector-effect="non-scaling-stroke"
                        stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"
                        class="stroke-primary-500 dark:stroke-primary-400" />
                </svg>
            </div>
        @endif
    </a>
@endforeach
