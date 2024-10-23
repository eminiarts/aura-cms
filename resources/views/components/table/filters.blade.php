<div>

    @php
        // ray($this->filters)
    @endphp
    <div>
        {{-- select dropdown with filters @foreach($this->userFilters as $userFilter)--}}
        <x-aura::input.select
            wire:model="selectedFilter"
            :options="['' => __('Select a filter')] + collect($this->userFilters)->mapWithKeys(fn($filter, $slug) => [$slug => $filter['name']])->toArray()"
            placeholder="{{ __('Select a filter') }}"
        />

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

    <p class="block mt-6 font-semibold">{{ __('Custom Filters') }}</p>


        @forelse($filters['custom'] as $key => $f)

        <div class="p-4 mt-4 bg-white rounded-md border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">

            <div class="flex justify-between">
                <div class="text-base font-semibold">
                    {{-- {{ $this->fieldsForFilter[$filters['custom'][$key]['name']]['name'] ?? '' }} --}}
                    <div class="mt-0 mb-0 w-24">
                        <x-aura::input.select wire:model="filters.custom.{{ $key }}.main_operator" wire:change="$refresh" id="filters_main_operator_{{ $key}}" name="filters_main_operator_{{ $key}}" :options="['and' => __('AND'), 'or' => __('OR')]" size="xs">
                        </x-aura::input.select>
                    </div>
                </div>
                <div>
                    <div class="-mt-1 -mr-1">
                        <x-aura::button.transparent size="xs" wire:click="removeCustomFilter('{{ $key }}')">
                            <x-aura::icon class="text-red-600" icon="close" size="xs"/>
                        </x-aura::button.transparent>
                    </div>
                </div>
            </div>



            <div class="flex space-x-1">
                <div class="mt-2 mb-0 w-full">
                    <x-aura::input.select
                        wire:model="filters.custom.{{ $key }}.name"
                        wire:change="$refresh"
                        id="filters_field_{{ $key}}"
                        name="filters_field_{{ $key}}"
                        size="xs"
                        :options="collect($this->fieldsForFilter)->mapWithKeys(function ($field, $slug) {
                            return [$slug => $field['name']];
                        })->toArray()"
                    >
                    </x-aura::input.select>
                </div>

            <div class="mt-2 mb-0 w-full">
                <x-aura::input.select
                    wire:model="filters.custom.{{ $key }}.operator"
                    id="filters_operator_{{ $key}}"
                    name="filters_operator_{{ $key}}"
                    size="xs"
                    :options="isset($filters['custom'][$key]['name']) && isset($this->fieldsForFilter[$filters['custom'][$key]['name']])
                        ? $this->fieldsForFilter[$filters['custom'][$key]['name']]['filterOptions']
                        : []"
                >
                </x-aura::input.select>
            </div>
            </div>

            <div class="mt-2 mb-0 w-full">
                <div class="w-full">
                    @php
                            $fieldType = $this->fieldsForFilter[$filters['custom'][$key]['name']]['type'];
                        @endphp
                        @if($fieldType === 'Select')
                            <x-aura::input.select
                                wire:model="filters.custom.{{ $key }}.value"
                                size="xs"
                                :options="collect($this->fieldsForFilter[$filters['custom'][$key]['name']]['filterValues'])->pluck('value', 'key')->prepend(__('Select a value'), '')"
                            >
                            </x-aura::input.select>
                        @else
                            <x-aura::input.wrapper placeholder="Value" error="filters.custom.{{ $key }}.value">
                                <x-aura::input.text wire:model="filters.custom.{{ $key}}.value" size="xs" placeholder="Value"></x-aura::input.text>
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
