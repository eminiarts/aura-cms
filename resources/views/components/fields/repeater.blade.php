@php
    $slug = $field['slug'];
    $values = data_get($this->post['fields'], $slug);
@endphp

{{-- @dump($field) --}}
{{-- @dd($this->post['fields'][$slug], $slug);
@dd($field['field']->transform($field['fields'],$this->post['fields'][$slug]))  --}}

<x-aura::fields.wrapper :field="$field">

    <div class="flex flex-col">

        @if(optional($values) && $values)
            @foreach($field['field']->transform($field, $values) as $key => $group)
                <div class="flex flex-wrap items-center -mx-0" wire:key="repeater-{{ $key }}">

                    <div class="flex flex-wrap items-center flex-1 space-x-0">
                    @foreach($group as $field)
                        <x-dynamic-component :component="$field['field']->component" :field="$field" />
                    @endforeach
                    </div>

                    <div class="w-4 mt-10 ml-4">
                        <x-aura::icon icon="chevron-up" size="xs" class="text-gray-300 cursor-pointer hover:text-gray-500" wire:click="moveRepeaterUp('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                        <x-aura::icon icon="minus" size="xs" class="text-gray-300 cursor-pointer hover:text-red-500" wire:click="removeRepeater('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                        <x-aura::icon icon="chevron-down" size="xs" class="text-gray-300 cursor-pointer hover:text-gray-500" wire:click="moveRepeaterDown('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="mt-4">
        <x-aura::button.border wire:click="addRepeater('{{ $slug }}')" >Add row</x-aura::button.border>
    </div>

</x-aura::fields.wrapper>
