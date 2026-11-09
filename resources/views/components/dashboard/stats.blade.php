@props(['stats', 'calm' => false])

{{-- One anatomy for every card: label and icon, the total with a 30-day trend beside it, then the
     count of new records in the last 30 days and the change against the 30 days before.
     The trend is the running total of new records. It is a row decision: drawn on every card when
     each card had records on at least three days of the month, otherwise on none, so the row never
     mixes charted and bare cards. The delta pill only appears on cards with a previous period to
     compare against; the others show no pill (the line keeps its height). Calm cards (an empty
     workspace) keep label and total; a zero stays gray-900 (a muted zero read as disabled) and the
     "No … yet" caption below says what it counts. Phones: a 2x2 grid; the trend is a desktop detail. --}}
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
        $series = array_values(array_map('intval', $stat['series'] ?? []));
        $showTrend = $rowTrend;
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
        class="flex relative flex-col col-span-6 p-4 min-w-0 rounded-xl ring-1 shadow-xs transition duration-150 sm:p-5 xl:col-span-3 bg-white dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10 hover:ring-gray-950/20 dark:hover:ring-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 group" data-stat-card>
        <div class="flex gap-3 justify-between items-center">
            <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ $stat['name'] }}</span>
            <span class="text-gray-400 transition-colors duration-150 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300 [&>svg]:size-4 shrink-0" aria-hidden="true">
                {!! $stat['icon'] !!}
            </span>
        </div>

        <div class="flex gap-4 justify-between items-end mt-3 h-[1.875rem]">
            <span class="text-gray-900 aura-stat-value dark:text-white">{{ number_format($stat['total']) }}</span>

            @if ($showTrend)
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" width="{{ $chartWidth }}" height="{{ $chartHeight }}" class="hidden shrink-0 w-28 h-7 sm:block" aria-hidden="true" data-stat-trend>
                    <polygon points="1,{{ $chartHeight }} {{ $points }} {{ $chartWidth - 1 }},{{ $chartHeight }}" class="fill-primary-500/10 dark:fill-primary-400/15" />
                    <polyline points="{{ $points }}" fill="none" class="stroke-primary-500 dark:stroke-primary-400" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" />
                </svg>
            @endif
        </div>

        @if ($calm && ! $stat['total'])
            {{-- The caption says what the zero counts. --}}
            <p class="mt-3 h-5 text-xs leading-5 text-gray-500 truncate dark:text-gray-400">{{ __('No :items yet', ['items' => Str::lower($stat['name'])]) }}</p>
        @endif

        {{-- A calm card with records keeps the count line (without the pill), so it is as tall as the captioned zeros beside it. --}}
        @unless ($calm && ! $stat['total'])
            {{-- Fixed h-5 line: a card with a delta pill is as tall as one without, so cards in a row match.
                 Phones say "+10 · 30d" (the full phrase stays for screen readers) so count and pill fit side by side. --}}
            <div class="flex gap-2 items-center mt-3 h-5 min-w-0 text-xs">
                <span class="truncate {{ $current > 0 ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500' }}">
                    <span class="font-medium tabular-nums">{{ $current > 0 ? '+'.number_format($current) : '0' }}</span>
                    <span class="sm:hidden" aria-hidden="true"><span class="text-gray-300 dark:text-gray-600">&middot;</span> {{ __('30d') }}</span><span class="sr-only sm:not-sr-only">{{ __('in the last 30 days') }}</span>
                </span>

                @if ($delta !== null)
                    <span class="inline-flex items-center gap-0.5 ml-auto h-5 rounded-full px-1.5 font-medium shrink-0 {{ $delta > 0 ? 'bg-green-50 text-green-700 dark:bg-green-900/40 dark:text-green-400' : ($delta < 0 ? 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-400' : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300') }}"
                        title="{{ __(':current new vs :previous in the previous 30 days', ['current' => $current, 'previous' => $stat['previous'] ?? 0]) }}">
                        @if ($delta !== 0)
                            <svg class="w-3 h-3 {{ $delta < 0 ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 19V5M5 12l7-7 7 7" />
                            </svg>
                        @endif
                        {{ abs((int) $deltaPercent) }}%
                    </span>
                @endif
            </div>
        @endunless
    </a>
@endforeach
