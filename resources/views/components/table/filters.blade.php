<div>

    @php
    @endphp
    @once
    @push('scripts')
        @vite(['resources/js/tagify.js'], 'vendor/aura/libs')
    @endpush
@endonce
    <div>
        {{-- select dropdown with filters @foreach($this->userFilters as $userFilter)--}}
        <x-aura::input.select
            wire:model.live="selectedFilter"
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

    <p class="block mt-6 font-semibold">{{ __('Custom Filters') }}</p>

    <div wire:key="filters-custom-{{ $selectedFilter }}">
        @foreach($filters['custom'] as $groupKey => $group)
            <div class="px-3 py-3 mt-4 bg-white rounded-md border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="flex justify-between items-center mb-2">
                    <div class="text-base font-semibold">
                        @if($groupKey > 0)
                            <x-aura::input.select
                                wire:model="filters.custom.{{ $groupKey }}.operator"
                                :options="['and' => __('AND'), 'or' => __('OR')]"
                                size="xs"
                            />
                        @else
                            <span class="text-sm">{{ __('Filter Group') }} {{ $groupKey + 1 }}</span>
                        @endif
                    </div>
                    <div class="mr-2">
                        <x-aura::button.transparent size="icon-xs" wire:click="removeFilterGroup({{ $groupKey }})">
                        <x-aura::icon class="text-red-600" icon="trash" size="xs"/>
                    </x-aura::button.transparent>
                    </div>
                </div>

                @foreach($group['filters'] as $filterKey => $filter)
                <div class="p-2 mt-2 bg-gray-50 rounded border border-gray-200/50 dark:bg-gray-700">
                    <div class="flex space-x-1">
                        @if($filterKey > 0)
                        <div class="w-16 shrink-0">
                            <x-aura::input.select
                                wire:model="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.main_operator"
                                :options="['and' => __('AND'), 'or' => __('OR')]"
                                size="xs"
                            />
                        </div>
                        @endif
                        <div class="flex-1">
                            <x-aura::input.select
                                wire:model="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.name"
                                wire:change="$refresh"
                                :options="collect($this->fieldsForFilter)->mapWithKeys(function ($field, $slug) {
                                    return [$slug => $field['name']];
                                })->toArray()"
                                size="xs"
                            />
                        </div>
                        <div class="flex-1">
                            <x-aura::input.select
                                wire:model="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.operator"
                                :options="isset($filter['name']) && isset($this->fieldsForFilter[$filter['name']])
                                    ? $this->fieldsForFilter[$filter['name']]['filterOptions']
                                    : []"
                                size="xs"
                            />
                        </div>


                        <x-aura::button.transparent size="icon-xs" wire:click="removeFilter({{ $groupKey }}, {{ $filterKey }})">
                            <x-aura::icon class="text-red-600" icon="trash" size="xs"/>
                        </x-aura::button.transparent>
                    </div>

                    <div class="mt-1 w-full">
                        <div class="w-full">
                            @php
                                    $fieldType = $this->fieldsForFilter[$filters['custom'][$groupKey]['filters'][$filterKey]['name']]['type'];
                                @endphp
                                @if($fieldType === 'Select')
                                    <x-aura::input.select
                                        wire:model="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.value"
                                        size="xs"
                                        :options="collect($this->fieldsForFilter[$filters['custom'][$groupKey]['filters'][$filterKey]['name']]['filterValues'])->pluck('value', 'key')->prepend(__('Select a value'), '')"
                                    >
                                    </x-aura::input.select>
                                @elseif($fieldType === 'AdvancedSelect')
                                    @php
                                        $field = $this->getFields[$filters['custom'][$groupKey]['filters'][$filterKey]['name']];
                                        $mode = 'create';

                                        $this->filters['custom'][$groupKey]['filters'][$filterKey]['options'] = [
                                            'resource_type' => $field['resource'],
                                        ];
                                    @endphp

                                    <div>
                                        <x-dynamic-component :component="$field['field']->filter()" :field="$field" wire:key="test2" model="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.value" size="xs" />
                                    </div>

                                @elseif($fieldType === 'Tags')
                                    <div class="flex flex-wrap gap-2">
                                        @php
                                            $field = $this->getFields[$filters['custom'][$groupKey]['filters'][$filterKey]['name']];

                                            // $form = [
                                            //     'fields' => [
                                            //         $field['slug'] => [184],
                                            //     ],
                                            // ];
                                            $this->filters['custom'][$groupKey]['filters'][$filterKey]['options'] = [
                                                'resource_type' => $field['resource'],
                                            ];
                                            $mode = 'create';
                                        @endphp

                                        <x-dynamic-component :component="$field['field']->filter()" :field="$field" wire:key="test" model="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.value" />

                                        {{-- @foreach($this->getFields[$filters['custom'][$groupKey]['filters'][$filterKey]['name']]['filterValues'] as $key => $value)
                                            <label class="inline-flex items-center">
                                                <input type="checkbox"
                                                       wire:model="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.value"
                                                       value="{{ $key }}"
                                                       class="w-4 h-4 transition duration-150 ease-in-out form-checkbox text-primary-600">
                                                <span class="ml-2 text-sm">{{ $value }}</span>
                                            </label>
                                        @endforeach --}}
                                    </div>
                                @else
                                    <x-aura::input.wrapper placeholder="Value" error="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.value">
                                        <x-aura::input.text wire:model="filters.custom.{{ $groupKey }}.filters.{{ $filterKey }}.value" size="xs" placeholder="Value"></x-aura::input.text>
                                    </x-aura::input.wrapper>
                                @endif
                        </div>
                    </div>
                </div>
                @endforeach

                <x-aura::button.light size="xs" wire:click="addSubFilter({{ $groupKey }})" class="mt-2">
                    {{ __('Add Filter') }}
                </x-aura::button.light>
            </div>
        @endforeach
    </div>

    <x-aura::button.light size="xs" wire:click="addFilterGroup" class="mt-4">
        {{ __('Add Filter Group') }}
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
