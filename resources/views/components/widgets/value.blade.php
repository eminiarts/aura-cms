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
            </div>
        </div>

        <div>
            <span class="text-sm font-medium text-gray-500">from {{ $this->values['previous'] }}</span>
        </div>

    </div>
    @else
    <div class="p-2 animate-pulse">
        <div class="flex items-baseline justify-between mb-4 ">
            <div class="w-1/4 h-4 bg-gray-200 rounded"></div>
        </div>

        <div class="flex items-baseline justify-between mt-1 mb-2 md:block lg:flex">
            <div class="flex items-baseline text-4xl font-medium">
                <div class="w-16 h-4 bg-gray-200 rounded"></div>
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
