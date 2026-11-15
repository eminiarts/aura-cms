@props(['stats', 'calm' => false])

{{-- One anatomy for every card: label and icon, the total with a 30-day trend beside it, then a caption
     only where it adds information: what the last 30 days added when that is part of the total ("8 added
     in 30 days", the count in primary as a live figure) and the change against the 30 days before
     on a second line ("+33% vs previous 30 days", the figure colored; only with a previous period). Cards
     without a caption are shorter; the grid row stretches every card to one height.
     The trend is the running total of new records. It is a row decision: drawn on every card when
     each card had records on at least three days of the month, otherwise on none, so the row never
     mixes charted and bare cards. Calm cards (an empty workspace) keep label and total and say
     "No … yet" for a zero; a zero stays gray-900 (a muted zero read as disabled).
     Phones: a 2x2 grid; the trend is a desktop detail, the change line says "+33% vs prior". --}}
@php
    $qualifies = fn ($stat) => $stat['showSparkline'] ?? (count(array_filter(array_map('intval', $stat['series'] ?? []))) >= 3);
    $rowTrend = ! $calm && collect($stats)->isNotEmpty() && collect($stats)->every($qualifies);
    $chartWidth = 112;
    $chartHeight = 28;
@endphp
@foreach ($stats as $stat)
    @php
        $delta = $calm ? null : ($stat['delta'] ?? null);
        $deltaPercent = $stat['deltaPercent'] ?? null;
        $current = (int) ($stat['current'] ?? 0);
        $total = (int) ($stat['total'] ?? 0);
        $series = array_values(array_map('intval', $stat['series'] ?? []));
        $showTrend = $rowTrend;
        // A caption only when it adds to the total: some (not all) records are recent, or there is a
        // previous period to compare against. "All added in 30 days" said nothing the total did not.
        $showCaption = ($current > 0 && $current < $total) || $delta !== null;
        $points = '';

        if ($showTrend) {
            $cumulative = [];
            $sum = 0;

            foreach ($series as $value) {
                $sum += $value;
                $cumulative[] = $sum;
            }

            $max = max($sum, 1);
            $count = count($cumulative);
            $points = collect($cumulative)->map(function ($value, $index) use ($count, $chartWidth, $chartHeight, $max) {
                $x = $count > 1 ? $index / ($count - 1) * ($chartWidth - 2) + 1 : 1;
                $y = $chartHeight - 1.5 - $value / $max * ($chartHeight - 3);

                return round($x, 2).','.round($y, 2);
            })->implode(' ');
        }
    @endphp
    <a href="{{ $stat['url'] }}" wire:navigate
        class="flex relative flex-col col-span-6 p-4 min-w-0 rounded-panel ring-1 transition duration-150 sm:p-5 xl:col-span-3 bg-white dark:bg-gray-800 ring-aura-edge hover:ring-gray-950/20 dark:hover:ring-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 group" data-stat-card>
        <div class="flex gap-3 justify-between items-center">
            <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ $stat['name'] }}</span>
            <span class="text-gray-400 transition-colors duration-150 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300 [&>svg]:size-4 shrink-0" aria-hidden="true">
                {!! $stat['icon'] !!}
            </span>
        </div>

        <div class="flex gap-4 justify-between items-end mt-2.5 h-7">
            <span class="text-gray-900 aura-stat-value dark:text-white">{{ number_format($total) }}</span>

            @if ($showTrend)
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" width="{{ $chartWidth }}" height="{{ $chartHeight }}" class="hidden shrink-0 w-28 h-7 sm:block" aria-hidden="true" data-stat-trend>
                    <polygon points="1,{{ $chartHeight }} {{ $points }} {{ $chartWidth - 1 }},{{ $chartHeight }}" class="fill-primary-500/10 dark:fill-primary-400/15" />
                    <polyline points="{{ $points }}" fill="none" class="stroke-primary-500 dark:stroke-primary-400" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" />
                </svg>
            @endif
        </div>

        @if ($calm && ! $total)
            {{-- The caption says what the zero counts. --}}
            <p class="mt-3 text-xs leading-5 text-gray-500 truncate dark:text-gray-400" data-stat-change>{{ __('No :items yet', ['items' => Str::lower($stat['name'])]) }}</p>
        @elseif ($showCaption)
            {{-- Two short lines rather than one that a 2x2 phone card cannot hold: what was added, then the change. --}}
            <div class="mt-3 min-w-0 text-xs leading-5 text-gray-600 dark:text-gray-400" data-stat-change>
                <p class="truncate">
                    @if ($current > 0)
                        <span class="font-medium tabular-nums text-primary-700 dark:text-primary-300">{{ number_format($current) }}</span> {{ __('added in 30 days') }}
                    @else
                        {{ __('None added in 30 days') }}
                    @endif
                </p>
                @if ($delta !== null)
                    <p title="{{ __(':current new vs :previous in the previous 30 days', ['current' => $current, 'previous' => $stat['previous'] ?? 0]) }}">
                        @if ($delta !== 0)
                            <span class="sr-only">{{ $delta > 0 ? __('Increased by') : __('Decreased by') }}</span>
                        @endif
                        <span class="font-medium tabular-nums whitespace-nowrap {{ $delta > 0 ? 'text-green-700 dark:text-green-400' : ($delta < 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-300') }}">{{ $delta > 0 ? '+' : ($delta < 0 ? '−' : '') }}{{ abs((int) $deltaPercent) }}%</span>
                        {{-- A 2x2 phone card holds the short form; the line above names the 30 days. --}}
                        <span class="sm:hidden">{{ __('vs prior') }}</span><span class="hidden sm:inline">{{ __('vs previous 30 days') }}</span>
                    </p>
                @endif
            </div>
        @endif
    </a>
@endforeach
