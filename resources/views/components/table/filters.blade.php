<div>

    @php
        // ray($this->filters)
    @endphp
    <div>
        {{-- select dropdown with filters @foreach($this->userFilters as $userFilter)--}}
        <select wire:model="selectedFilter" class="block w-full px-3 py-2 pl-3 pr-10 mt-1 text-base bg-white border-gray-500/30 rounded-lg shadow-xs appearance-none focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 sm:text-sm">
            <option value="">
                {{ __('Select a filter') }}
            </option>
            @foreach($this->userFilters as $name => $userFilter)
            <option value="{{ $name }}">{{ $userFilter['name'] }}</option>
            @endforeach
        </select>

        {{-- if a filter is selected show the filter --}}
        @if($this->selectedFilter)
        <div class="flex items-center justify-between my-4">
            <h4 class="font-semibold text-primary-600">Filter: {{ $this->userFilters[$this->selectedFilter]['name'] }}</h4>
            <x-aura::button.transparent size="xs" wire:click="deleteFilter('{{ $this->selectedFilter }}')">
            <x-aura::icon icon="trash" size="xs"/> {{ __('Delete Filter') }}
        </x-aura::button.transparent>
        </div>
        @endif


    </div>

    <hr class="my-4 border-t border-gray-400/30 dark:border-gray-700">

    @forelse($this->model->taxonomyFields() as $field)
    <div>
        <h4 class="my-4 font-semibold text-primary-600">{{ $field['name'] }}</h4>
        <div class="flex flex-col space-y-2">
            @if(array_key_exists('resource', $field))
                @foreach (app($field['resource'])->get() as $taxonomy)
                <div class="flex items-center">
                    <x-aura::input.checkbox wire:model="filters.taxonomy.{{ $field['slug'] }}" name="taxonomy_{{ $taxonomy->id }}" id="taxonomy_{{ $taxonomy->id }}" :label="$taxonomy->title" :value="$taxonomy->id" />
                </div>
                @endforeach
            @else
                {{-- The 'resource' key is not defined in the $field array --}}
            @endif
        </div>
    </div>
    @empty
    @endforelse

    {{-- <hr class="my-4 border-t border-gray-400/30 dark:border-gray-700"> --}}

    <p class="block font-semibold">{{ __('Custom Filters') }}</p>

     {{-- @dump( $filters) --}}
        {{-- @dump( $this->fieldsForFilter) --}}

        @forelse($filters['custom'] as $key => $f)

        <div class="mt-6">
            <div class="flex justify-between">
                <div class="py-1 text-base font-semibold">Filter #{{ $key }}</div>

                <div>
                    <x-aura::button.transparent size="xs" wire:click="removeCustomFilter('{{ $key }}')">
                        <x-aura::icon class="text-red-600" icon="close" size="xs"/>
                    </x-aura::button.transparent>
                </div>
            </div>

            <div class="w-full mt-2 mb-0">
                <select wire:model="filters.custom.{{ $key }}.name" id="filters_field_{{ $key}}" name="filters_field_{{ $key}}" class="block w-full py-2 pl-3 pr-10 mt-1 text-base border-gray-500/30 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    @foreach($this->fieldsForFilter as $slug => $name)
                    <option value="{{ $slug }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full mt-2 mb-0">
                <select wire:model="filters.custom.{{ $key }}.operator" id="filters_operator_{{ $key}}" name="filters_operator_{{ $key}}" class="block w-full py-2 pl-3 pr-10 mt-1 text-base border-gray-500/30 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    <option value="contains">{{ __('contains') }}</option>
                    <option value="does_not_contain">{{ __('does not contain') }}</option>
                    <option value="is">{{ __('is') }}</option>
                    <option value="is_not">{{ __('is not') }}</option>
                    <option value="starts_with">{{ __('starts with') }}</option>
                    <option value="ends_with">{{ __('ends with') }}</option>
                    <option value="is_empty">{{ __('is empty') }}</option>
                </select>
            </div>

            <div class="w-full mt-2 mb-0">
                <div class="w-full">
                    <x-aura::input.wrapper placeholder="Value" error="filters.custom.{{ $key }}.value">
                        <x-aura::input.text wire:model="filters.custom.{{ $key}}.value"></x-aura::input.text>
                    </x-aura::input.wrapper>
                </div>
            </div>
        </div>

        @empty

        @endforelse

        <x-aura::button.light size="xs" wire:click="addFilter" class="mt-4">
            {{ __('Add Filter') }}
        </x-aura::button.light>


        <x-aura::button.transparent size="xs" wire:click="resetFilter" class="mt-4">
            {{ __('Reset Filter') }}
        </x-aura::button.transparent>


        {{-- <x-aura::button.transparent size="xs" wire:click="saveFilter" class="mt-4">Save as Template</x-aura::button.transparent> --}}


    </div>

<div class="flex justify-between items-center">
    <x-aura::button.primary wire:click="$refresh" class="mt-4">{{ __('Search') }}</x-aura::button.primary>
        @include('aura::components.table.filters-save-as-template')

</div>