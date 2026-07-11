@if (!empty($this->userFilters))
    <div class="flex flex-wrap gap-1 items-center p-1 my-4 max-w-full bg-gray-50 rounded-lg dark:bg-white/5 w-fit">

        <button type="button" wire:click="$set('selectedFilter', null)"
            class="flex items-center px-3 py-1.5 space-x-2 text-sm rounded-md transition cursor-pointer
                   {{ $this->selectedFilter == null
                        ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-700 dark:text-white dark:ring-white/10 font-medium'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
            {{ __('All') }}
        </button>

        @foreach ($this->userFilters as $name => $userFilter)
            <button type="button" wire:click="$set('selectedFilter', '{{ $name }}')"
                class="flex items-center px-3 py-1.5 space-x-2 text-sm rounded-md transition cursor-pointer
                       {{ $this->selectedFilter == $name
                            ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-700 dark:text-white dark:ring-white/10 font-medium'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">

                @if(optional($userFilter)['icon'])
                    <span class="[&>svg]:w-4 [&>svg]:h-4">{!! $userFilter['icon'] !!}</span>
                @endif

                <span>{!! $userFilter['name'] !!}</span>
            </button>
        @endforeach
    </div>
@else
    <div class="mb-4 w-full"></div>
@endif
