@if (!empty($this->userFilters))
    <div class="flex flex-wrap gap-1 items-center p-1 my-4 max-w-full rounded-lg bg-gray-950/[0.04] dark:bg-white/5 w-fit">

        <button type="button" wire:click="$set('selectedFilter', null)"
            class="flex items-center px-3 py-1.5 space-x-2 text-sm rounded-md cursor-pointer transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                   {{ $this->selectedFilter == null
                        ? 'bg-white text-gray-900 font-medium shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10'
                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200' }}">
            {{ __('All') }}
        </button>

        @foreach ($this->userFilters as $name => $userFilter)
            <button type="button" wire:click="$set('selectedFilter', '{{ $name }}')"
                class="flex items-center px-3 py-1.5 space-x-2 text-sm rounded-md cursor-pointer transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                       {{ $this->selectedFilter == $name
                            ? 'bg-white text-gray-900 font-medium shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10'
                            : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200' }}">

                @if(optional($userFilter)['icon'])
                    <span>{{ $userFilter['icon'] }}</span>
                @endif

                <span>{{ $userFilter['name'] }}</span>
            </button>
        @endforeach
    </div>
@else
    <div class="mb-4 w-full"></div>
@endif
