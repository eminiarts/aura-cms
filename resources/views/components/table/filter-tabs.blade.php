@if (!empty($this->userFilters))
    <div class="flex my-4 space-x-4">

        <div class="cursor-pointer flex items-center space-x-2 p-2
                   {{ $this->selectedFilter == null ? 'border-b border-black border-1 text-gray-900 dark:text-gray-200 dark:border-gray-100 font-semibold' : 'text-gray-500' }}"
            wire:click="$set('selectedFilter', null)">
            {{ __('All') }}
        </div>

        @foreach ($this->userFilters as $name => $userFilter)
            <div class="cursor-pointer flex items-center space-x-2 p-2
                       {{ $this->selectedFilter == $name ? 'border-b border-black border-1 text-gray-900 dark:text-gray-200 dark:border-gray-100 font-semibold' : 'text-gray-500' }}"
                wire:click="$set('selectedFilter', '{{ $name }}')">

                <div>
                    {!! optional($userFilter)['icon'] !!}
                </div>

                <div>
                    {!! $userFilter['name'] !!}
                </div>
            </div>
        @endforeach
    </div>
    @else
    <div class="mb-4 w-full"></div>
@endif
