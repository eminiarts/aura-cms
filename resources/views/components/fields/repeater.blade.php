@php
    $slug = $field['slug'];
    $values = data_get($this->resource['fields'], $slug);
@endphp

{{-- @dump($field, $this->resource['fields'][$slug], $values, $field['field']->transform($field, $values)) --}}
{{-- @dd($this->resource['fields'][$slug], $slug);
@dd($field['field']->transform($field['fields'],$this->resource['fields'][$slug]))  --}}
{{-- @dump($this->resource['fields']) --}}


{{-- <input class="shadow-xs border border-gray-500/30 appearance-none px-3 py-2 focus:outline-none w-full ring-gray-900/10 focus:ring focus:border-primary-300 focus:ring-primary-300  focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 disabled:opacity-75 disabled:bg-gray-100 disabled:dark:bg-gray-800 rounded-none bg-white dark:bg-gray-900 dark:border-gray-700 z-[1] rounded-l-lg  rounded-r-lg" type="text" wire:model="resource.fields.variations.0.value" error="resource.fields.variations.0.value" placeholder="Value" id="post-field-variations.0.value"> --}}

<x-aura::fields.wrapper :field="$field">

    <div class="flex flex-col">
        <ul role="list" class="overflow-hidden bg-white divide-y divide-gray-100 ring-1 shadow-sm ring-gray-900/5 sm:rounded-xl">
        @if(optional($values) && $values)
            @foreach($field['field']->transform($field, $values) as $key => $group)
                <li class="flex relative gap-x-6 justify-between pr-4 pb-4 hover:bg-gray-50">
                    <div class="flex flex-wrap items-center -mx-0" wire:key="repeater-{{ $key }}">

                        <div class="flex flex-wrap flex-1 items-center space-x-0">
                        @foreach($group as $field)
                            <x-dynamic-component :component="$field['field']->component" :field="$field" />
                        @endforeach
                        </div>

                        <div class="mt-10 ml-4 w-4">
                            <x-aura::icon icon="chevron-up" size="xs" class="text-gray-300 cursor-pointer hover:text-gray-500" wire:click="moveRepeaterUp('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                            <x-aura::icon icon="minus" size="xs" class="text-gray-300 cursor-pointer hover:text-red-500" wire:click="removeRepeater('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                            <x-aura::icon icon="chevron-down" size="xs" class="text-gray-300 cursor-pointer hover:text-gray-500" wire:click="moveRepeaterDown('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                        </div>
                    </div>
                </li>
            @endforeach
        @endif
        </ul>
    </div>

    <div class="mt-4">
        <x-aura::button.border wire:click="addRepeater('{{ $slug }}')" >Add row</x-aura::button.border>
    </div>

</x-aura::fields.wrapper>
