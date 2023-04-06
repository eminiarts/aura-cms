<div class="aura-card" @if (!$isCached) wire:init="loadWidget" @endif wire:key="total_posts">

    @if($loaded)

    {{-- @dump($this->values, $this->cacheKey, $this->start, $this->end) --}}
    <div class="p-2">
        <div class="flex items-baseline justify-between mb-4">
            <span class="text-sm font-semibold">{{ $widget['name'] }}</span>
        </div>

        <div class="flex items-baseline justify-between mt-1 mb-2 md:block lg:flex">
            <div class="flex items-baseline text-4xl font-medium">
                {{ $this->values['current'] ?? 'N/A' }}
            </div>

            <div>
                @if(optional($widget)['goal'] && is_numeric($widget['goal']))
                    @php
                        $currentValue = str_replace("'", "", $this->values['current']);
                        $goal = str_replace("'", "", $widget['goal']);
                        $change = round(($currentValue / $goal) * 100, 2);
                    @endphp

                    <div
                        class="inline-flex items-baseline rounded-full px-2.5 py-0.5 text-sm font-medium bg-primary-50 text-primary-600 md:mt-2 lg:mt-0">
                        <span class="sr-only"> Increased by </span>
                        {{ $change }}%
                    </div>
                @else
                    @if($this->values['change'] >= 0)
                    <div
                        class="inline-flex items-baseline rounded-full px-2.5 py-0.5 text-sm font-medium bg-green-100 text-green-800 md:mt-2 lg:mt-0">
                        <svg class="-ml-1 mr-0.5 h-4 w-4 flex-shrink-0 self-center text-green-500" viewBox="0 0 24 24"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 18L18 6M18 6H10M18 6V14" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>


                        <span class="sr-only"> Increased by </span>
                        {{ $this->values['change'] }}%
                    </div>
                    @else
                    <div
                        class="inline-flex items-baseline rounded-full px-2.5 py-0.5 text-sm font-medium bg-red-100 text-red-800 md:mt-2 lg:mt-0">
                        <svg class="-ml-1 mr-0.5 h-4 w-4 flex-shrink-0 self-center text-red-500" viewBox="0 0 24 24"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 6L18 18M18 18V10M18 18H10" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="sr-only"> Decreased by </span>
                        {{ $this->values['change'] }}%
                    </div>
                    @endif
                @endif
            </div>
        </div>

        <div>

            @if(optional($widget)['goal'] && is_numeric($widget['goal']))
            @php
                $currentValue = str_replace("'", "", $this->values['current']);
            @endphp
                <div class="flex items-end h-6">
                    <div class="flex flex-col items-stretch w-full">
                        <x-aura::tippy text="{{ $currentValue }} / {{ intval($widget['goal']) }}" class="bg-primary-100">
                        <div class="block">
                            <div class="w-full h-2 bg-gray-200 rounded">
                                <div class="h-2 rounded bg-primary-500" style="max-width: 100%; width: {{ intval(str_replace("'", "", $this->values['current'])) / intval($widget['goal']) * 100 }}%"></div>
                            </div>
                        </div>
                    </x-aura::tippy>
                    </div>
                </div>
            @else
                <span class="text-sm font-medium text-gray-500">from {{ $this->values['previous'] }}</span>
            @endif
        </div>

    </div>
    @else
    <div class="p-2 animate-pulse">
        <div class="flex items-baseline justify-between mb-4 ">
            <div class="w-1/4 h-4 mt-2 bg-gray-200 rounded"></div>
        </div>

        <div class="flex items-baseline justify-between mt-1 mb-2 md:block lg:flex">
            <div class="flex items-baseline text-4xl font-medium">
                <div class="w-16 h-6 mb-4 bg-gray-200 rounded"></div>
            </div>
            <div>
                <div class="w-12 h-4 bg-gray-200 rounded"></div>
            </div>
        </div>

        <div>
            <span class="text-sm font-medium text-gray-500">
                <div class="w-16 h-4 bg-gray-200 rounded"></div>
            </span>
        </div>
    </div>

    @endif
</div>
