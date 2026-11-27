@if ($compact)
{{-- Compact (index strip): 12px name over a 20px value, the change as quiet colored text. --}}
<div class="flex flex-col justify-center px-3 py-3 h-full min-w-0 sm:px-4" @if (!$isCached) wire:init="loadWidget" @endif wire:key="value-widget-{{ $widget['slug'] ?? '' }}">
    @if($loaded)
        @php
            $hasPrevious = optional($widget)['previous'] !== false
                && (float) str_replace("'", '', $this->values['previous'] ?? '0') != 0.0;
        @endphp
        {{-- Phones fit three cells to a row: the name may take two lines there. --}}
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 line-clamp-2 sm:line-clamp-none sm:truncate">{{ $this->title() }}</span>
        {{-- The change names what it compares against ("+50% vs previous 30 days"). Phones keep the figure only (the
             words stay for screen readers): the range select above the strip names the period. --}}
        <div class="flex flex-wrap gap-x-2 items-baseline mt-1 min-w-0">
            <span class="text-xl font-semibold tracking-tight tabular-nums text-gray-900 shrink-0 dark:text-white">{{ $this->values['current'] ?? 'N/A' }}</span>
            @if($hasPrevious)
                <span class="text-xs font-medium tabular-nums shrink-0 {{ $this->values['change'] >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                    <span class="sr-only">{{ $this->values['change'] >= 0 ? __('Increased by') : __('Decreased by') }}</span>
                    {{ $this->values['change'] >= 0 ? '+' : '' }}{{ $this->values['change'] }}%
                </span>
                @if ($this->comparisonLabel())
                    <span class="min-w-0 text-xs text-gray-500 truncate max-sm:sr-only dark:text-gray-400">{{ $this->comparisonLabel() }}</span>
                @endif
            @endif
        </div>
    @else
        {{-- Static placeholder, no pulse. --}}
        <div aria-hidden="true">
            <div class="w-24 h-3 bg-gray-100 rounded dark:bg-gray-700/60"></div>
            <div class="mt-2 w-12 h-6 bg-gray-100 rounded-md dark:bg-gray-700/60"></div>
        </div>
    @endif
</div>
@else
<div class="aura-card h-full" @if (!$isCached) wire:init="loadWidget" @endif wire:key="value-widget-{{ $widget['slug'] ?? '' }}">

    @if($loaded)
        @php
            $hasPrevious = optional($widget)['previous'] !== false
                && (float) str_replace("'", '', $this->values['previous'] ?? '0') != 0.0;
        @endphp
        <div class="flex flex-col justify-between h-full">
            <div class="flex gap-2 justify-between items-center">
                <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ $this->title() }}</span>

                @if(optional($widget)['goal'] && is_numeric($widget['goal']))
                    @php
                        $currentValue = str_replace("'", '', $this->values['current']);
                        $goal = str_replace("'", '', $widget['goal']);
                        $change = round(($currentValue / $goal) * 100, 2);
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-primary-50 text-primary-700 dark:bg-primary-900/40 dark:text-primary-400 shrink-0">
                        {{ $change }}%
                    </span>
                @elseif($hasPrevious)
                    @if($this->values['change'] >= 0)
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-green-50 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400 shrink-0">
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 13V3.5M4.5 7 8 3.5 11.5 7" />
                            </svg>
                            <span class="sr-only">{{ __('Increased by') }}</span>
                            {{ $this->values['change'] }}%
                        </span>
                    @else
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-red-50 dark:bg-red-900/40 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400 shrink-0">
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 3v9.5M4.5 9 8 12.5 11.5 9" />
                            </svg>
                            <span class="sr-only">{{ __('Decreased by') }}</span>
                            {{ $this->values['change'] }}%
                        </span>
                    @endif
                @endif
            </div>

            <div class="flex gap-2 items-baseline mt-3">
                <span class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $this->values['current'] ?? 'N/A' }}</span>

                @if(!optional($widget)['goal'] && $hasPrevious)
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('from') }} {{ $this->values['previous'] }}</span>
                @endif
            </div>

            @if(optional($widget)['goal'] && is_numeric($widget['goal']))
                @php
                    $currentValue = str_replace("'", '', $this->values['current']);
                @endphp
                <div class="mt-4">
                    <x-aura::tippy text="{{ $currentValue }} / {{ intval($widget['goal']) }}" class="bg-primary-100">
                        <div class="block">
                            <div class="overflow-hidden w-full h-2 bg-gray-100 rounded-full dark:bg-gray-700/60">
                                <div class="h-2 rounded-full bg-primary-500 dark:bg-primary-400 transition-[width] duration-300" style="max-width: 100%; width: {{ intval($currentValue) / intval($widget['goal']) * 100 }}%"></div>
                            </div>
                        </div>
                    </x-aura::tippy>
                </div>
            @endif
        </div>
    @else
        <div class="animate-pulse" aria-hidden="true">
            <div class="flex justify-between items-center">
                <div class="w-1/3 h-4 bg-gray-100 rounded-md dark:bg-gray-700/60"></div>
                <div class="w-12 h-5 bg-gray-100 rounded-full dark:bg-gray-700/60"></div>
            </div>
            <div class="mt-4 w-24 h-9 bg-gray-100 rounded-lg dark:bg-gray-700/60"></div>
        </div>
    @endif
</div>
@endif
