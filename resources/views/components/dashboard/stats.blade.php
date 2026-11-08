@props(['stats', 'calm' => false])

{{-- Every card has the same four rows: label + icon, value, delta slot, sparkline slot.
     A resource without history shows a dash in the delta slot and keeps the empty chart height,
     so the cards line up whatever the data. Calm cards (an empty workspace) keep only label and a muted value. --}}
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

        <span class="mt-2 text-3xl font-semibold tracking-tight {{ $calm && ! $stat['total'] ? 'text-gray-300 dark:text-gray-600' : 'text-gray-900 dark:text-white' }}">{{ number_format($stat['total']) }}</span>

        @unless ($calm)
        {{-- Delta slot: change against the previous 30 days, or a dash when there is no previous period to compare with. --}}
        <div class="flex gap-2 items-center mt-1.5 h-5 min-w-0">
            @if ($delta === null)
                <span class="aura-meta">&mdash;</span>
            @else
                <span class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs font-medium {{ $delta > 0 ? 'bg-green-50 text-green-700 dark:bg-green-900/40 dark:text-green-400' : ($delta < 0 ? 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-400' : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300') }}"
                    title="{{ __(':current new vs :previous in the previous 30 days', ['current' => $stat['current'], 'previous' => $stat['previous']]) }}">
                    @if ($delta !== 0)
                        <svg class="w-3 h-3 {{ $delta < 0 ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 19V5M5 12l7-7 7 7" />
                        </svg>
                    @endif
                    {{ abs((int) $deltaPercent) }}%
                </span>
                <span class="truncate aura-meta">{{ __('vs previous 30 days') }}</span>
            @endif
        </div>

        {{-- Sparkline slot, fixed height, sits on the bottom padding so the cards in a row line up.
             On phones the cards stack, so an empty slot collapses. The primary color: the one place a stat card carries color. --}}
        <div class="mt-auto pt-3 h-11 {{ $showSparkline ? '' : 'hidden sm:block' }}">
            @if ($showSparkline)
                <svg viewBox="0 0 100 30" preserveAspectRatio="none" class="w-full h-8" aria-hidden="true">
                    <polygon points="{{ $stat['sparkline']['area'] }}" class="fill-primary-500/[0.08] transition-colors duration-150 dark:fill-primary-400/10 group-hover:fill-primary-500/[0.14] dark:group-hover:fill-primary-400/[0.16]" />
                    <polyline points="{{ $stat['sparkline']['line'] }}" fill="none" vector-effect="non-scaling-stroke"
                        stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"
                        class="stroke-primary-500 dark:stroke-primary-400" />
                </svg>
            @endif
        </div>
        @endunless
    </a>
@endforeach
