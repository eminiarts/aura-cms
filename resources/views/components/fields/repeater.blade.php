@php
    $slug = $field['slug'];
@endphp

{{-- @dump($field) --}}
{{-- @dd($this->post['fields'][$slug], $slug);
@dd($field['field']->transform($field['fields'],$this->post['fields'][$slug]))  --}}

<x-fields.wrapper :field="$field">

    <div class="flex flex-col">

        @if(optional($this->post['fields'])[$slug])
        @foreach($field['field']->transform($field['fields'],$this->post['fields'][$slug]) as $key => $group)
            <div class="flex flex-wrap -mx-0 items-center" wire:key="repeater-{{ $key }}">

                <div class="flex flex-wrap flex-1 items-center space-x-0">
                @foreach($group as $field)
                    <x-dynamic-component :component="$field['field']->component" :field="$field" />
                @endforeach
                </div>

                <div class="w-4 ml-4 mt-10">
                    <x-icon icon="chevron-up" size="xs" class="text-gray-300 hover:text-gray-500 cursor-pointer" wire:click="moveRepeaterUp('{{ $slug }}', '{{ $key }}')"></x-icon>
                    <x-icon icon="minus" size="xs" class="text-gray-300 hover:text-red-500 cursor-pointer" wire:click="removeRepeater('{{ $slug }}', '{{ $key }}')"></x-icon>
                    <x-icon icon="chevron-down" size="xs" class="text-gray-300 hover:text-gray-500 cursor-pointer" wire:click="moveRepeaterDown('{{ $slug }}', '{{ $key }}')"></x-icon>
                </div>
            </div>
        @endforeach
        @endif
    </div>

    <div class="mt-4">
        <x-button.border wire:click="addRepeater('{{ $slug }}')" >Add row</x-button.border>
    </div>

</x-fields.wrapper>
