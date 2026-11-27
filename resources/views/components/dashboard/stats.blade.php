@props(['stats', 'calm' => false])

{{-- The count cards, one dashboard.stat per resource. One anatomy for every card: label and icon, the total with a 30-day trend beside it, then one footer line
     on every card: what the last 30 days added, never repeating the total ("All added in the last 30 days"
     when every record is new, else "8 added in the last 30 days", the count in the caption's gray), with the
     change against the 30 days before right-aligned on the same line ("+33%", the figure colored, "vs previous
     30 days" in its tooltip; only with a previous period). Phones say "in 30 days" and put the change on a
     second line; when one card in the row has a change, every footer keeps that second line, so the cards
     keep one height.
     The trend is the running total of new records. It is a row decision: drawn on every card when
     each card had records on at least three days of the month, otherwise on none, so the row never
     mixes charted and bare cards. Calm cards (an empty workspace) keep label and total only; a zero
     stays gray-900 (a muted zero read as disabled). Phones: a 2x2 grid; the trend is a desktop detail. --}}
@php
    $qualifies = fn ($stat) => $stat['showSparkline'] ?? (count(array_filter(array_map('intval', $stat['series'] ?? []))) >= 3);
    $rowTrend = ! $calm && collect($stats)->isNotEmpty() && collect($stats)->every($qualifies);
    $rowHasDelta = ! $calm && collect($stats)->contains(fn ($stat) => ($stat['delta'] ?? null) !== null);
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
    <x-aura::dashboard.stat :label="$stat['name']" :icon="$stat['icon']" :url="$stat['url']" :value="number_format($total)" class="col-span-6 xl:col-span-3">
        @if ($showTrend)
            <x-slot:trend>
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" width="{{ $chartWidth }}" height="{{ $chartHeight }}" class="hidden shrink-0 w-28 h-7 sm:block" aria-hidden="true" data-stat-trend>
                    <polygon points="1,{{ $chartHeight }} {{ $points }} {{ $chartWidth - 1 }},{{ $chartHeight }}" class="fill-primary-500/10 dark:fill-primary-400/15" />
                    <polyline points="{{ $points }}" fill="none" class="stroke-primary-500 dark:stroke-primary-400" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" />
                </svg>
            </x-slot:trend>
        @endif

        @unless ($calm)
            <x-slot:footer>
                <p @class(['flex flex-col gap-x-2 mt-3 min-w-0 text-xs leading-5 text-gray-600 sm:flex-row sm:flex-wrap sm:justify-between sm:items-baseline dark:text-gray-300', 'max-sm:min-h-10' => $rowHasDelta]) data-stat-change>
                    @php
                        $allNew = $total > 0 && $current === $total;
                        $count = number_format($current);
                    @endphp
                    <span class="tabular-nums sm:hidden">{{ $allNew ? __('All added in 30 days') : __(':count added in 30 days', ['count' => $count]) }}</span>
                    <span class="tabular-nums max-sm:hidden">{{ $allNew ? __('All added in the last 30 days') : __(':count added in the last 30 days', ['count' => $count]) }}</span>
                    @if ($delta !== null)
                        <span class="font-medium tabular-nums whitespace-nowrap sm:ml-auto {{ $delta > 0 ? 'text-green-700 dark:text-green-400' : ($delta < 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-300') }}" title="{{ __('vs previous 30 days') }}">
                            @if ($delta !== 0)
                                <span class="sr-only">{{ $delta > 0 ? __('Increased by') : __('Decreased by') }}</span>
                            @endif
                            {{ $delta > 0 ? '+' : ($delta < 0 ? '−' : '') }}{{ abs((int) $deltaPercent) }}%<span class="sr-only"> {{ __('vs previous 30 days') }}</span>
                        </span>
                    @endif
                </p>
            </x-slot:footer>
        @endunless
    </x-aura::dashboard.stat>
@endforeach
