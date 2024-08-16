@php
    $slug = $field['slug'];
    $values = data_get($this->form['fields'], $slug);

if (optional($this)->model && isset($field['get']) && $field['get'] instanceof \Closure) {
    $values = call_user_func($field['get'], $this->model, $field, $values);

    $this->form['fields'][$slug] = $values;
}
@endphp

{{-- @dump($field, $this->form['fields'][$slug], $values, $field['field']->transform($field, $values)) --}}
{{-- @dd($this->form['fields'][$slug], $slug);
@dd($field['field']->transform($field['fields'],$this->form['fields'][$slug]))  --}}
{{-- @dump($this->form['fields']) --}}


{{-- <input class="shadow-xs border border-gray-500/30 appearance-none px-3 py-2 focus:outline-none w-full ring-gray-900/10 focus:ring focus:border-primary-300 focus:ring-primary-300  focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 disabled:opacity-75 disabled:bg-gray-100 disabled:dark:bg-gray-800 rounded-none bg-white dark:bg-gray-900 dark:border-gray-700 z-[1] rounded-l-lg  rounded-r-lg" type="text" wire:model="form.fields.variations.0.value" error="form.fields.variations.0.value" placeholder="Value" id="resource-field-variations.0.value"> --}}

<x-aura::fields.wrapper :field="$field">

    <div class="flex flex-col">
        <ul role="list" class="overflow-hidden border divide-y divide-gray-100 dark:divide-gray-700 sm:rounded-xl border-gray-500/30 dark:border-gray-700">
        @if(optional($values) && $values)
            {{-- @dump($this->form, $field, $values, $field['field']->transform($field, $values)) --}}
            @foreach($field['field']->transform($field, $values) as $key => $group)
                <li class="flex relative gap-x-6 justify-between pr-4 pb-4 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <div class="flex flex-wrap items-center -mx-0" wire:key="repeater-{{ $key }}">

                        <div class="flex flex-wrap flex-1 items-center space-x-0">
                        @foreach($group as $field)
                            <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" />
                        @endforeach
                        </div>

                        <div class="mt-10 ml-4 w-4">
                            <x-aura::icon icon="chevron-up" size="xs" class="text-gray-300 cursor-pointer hover:text-gray-500" wire:click="moveRepeaterUp('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                            <x-aura::icon icon="minus" size="xs" class="remove-repeater text-gray-300 cursor-pointer hover:text-red-500" wire:click="removeRepeater('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                            <x-aura::icon icon="chevron-down" size="xs" class="text-gray-300 cursor-pointer hover:text-gray-500" wire:click="moveRepeaterDown('{{ $slug }}', '{{ $key }}')"></x-aura::icon>
                        </div>
                    </div>
                </li>
            @endforeach
        @endif
        </ul>
    </div>

    <div class="mt-4">
        <x-aura::button.border class="add_repeater" wire:click="addRepeater('{{ $slug }}')" >Add row</x-aura::button.border>
    </div>

</x-aura::fields.wrapper>
