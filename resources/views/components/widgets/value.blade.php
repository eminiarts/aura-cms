<div class="aura-card h-full" @if (!$isCached) wire:init="loadWidget" @endif wire:key="value-widget-{{ $widget['slug'] ?? '' }}">

    @if($loaded)
        @php
            $hasPrevious = optional($widget)['previous'] !== false
                && (float) str_replace("'", '', $this->values['previous'] ?? '0') != 0.0;
        @endphp
        <div class="flex flex-col justify-between h-full">
            <div class="flex gap-2 justify-between items-center">
                <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ __($widget['name']) }}</span>

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
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 19V5M5 12l7-7 7 7" />
                            </svg>
                            <span class="sr-only">{{ __('Increased by') }}</span>
                            {{ $this->values['change'] }}%
                        </span>
                    @else
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-red-50 dark:bg-red-900/40 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400 shrink-0">
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12l7 7 7-7" />
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
