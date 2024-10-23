<div>

    @php
        // ray($this->filters)
    @endphp
    <div>
        {{-- select dropdown with filters @foreach($this->userFilters as $userFilter)--}}
        <select wire:model="selectedFilter" class="block px-3 py-2 pr-10 pl-3 mt-1 w-full text-base bg-white rounded-lg appearance-none border-gray-500/30 shadow-xs focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 sm:text-sm">
            <option value="">
                {{ __('Select a filter') }}
            </option>
            @foreach($this->userFilters as $slug => $userFilter)
            <option value="{{ $slug }}">{{ $userFilter['name'] }}</option>
            @endforeach
        </select>

        {{-- if a filter is selected show the filter --}}
        @if($this->selectedFilter)
        <div class="flex justify-between items-center my-4">
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

            <div class="mt-2 mb-0 w-full">
                <select wire:model="filters.custom.{{ $key }}.name" wire:change="$refresh" id="filters_field_{{ $key}}" name="filters_field_{{ $key}}" class="block py-2 pr-10 pl-3 mt-1 w-full text-base rounded-md border-gray-500/30 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    @foreach($this->fieldsForFilter as $slug => $field)
                    <option value="{{ $slug }}">{{ $field['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-2 mb-0 w-full">
                <select wire:model="filters.custom.{{ $key }}.operator" id="filters_operator_{{ $key}}" name="filters_operator_{{ $key}}" class="block py-2 pr-10 pl-3 mt-1 w-full text-base rounded-md border-gray-500/30 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    @if(isset($filters['custom'][$key]['name']) && isset($this->fieldsForFilter[$filters['custom'][$key]['name']]))
                        @foreach($this->fieldsForFilter[$filters['custom'][$key]['name']]['filterOptions'] as $operator => $label)
                        <option value="{{ $operator }}">{{ $label }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="mt-2 mb-0 w-full">
                <div class="w-full">
                    @php
                            $fieldType = $this->fieldsForFilter[$filters['custom'][$key]['name']]['type'];
                        @endphp
                        @if($fieldType === 'Select')
                            <select wire:model="filters.custom.{{ $key }}.value" class="block py-2 pr-10 pl-3 mt-1 w-full text-base rounded-md border-gray-500/30 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                <option value="">{{ __('Select a value') }}</option>
                                @foreach($this->fieldsForFilter[$filters['custom'][$key]['name']]['filterValues'] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <x-aura::input.wrapper placeholder="Value" error="filters.custom.{{ $key }}.value">
                                <x-aura::input.text wire:model="filters.custom.{{ $key}}.value"></x-aura::input.text>
                            </x-aura::input.wrapper>
                        @endif
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
