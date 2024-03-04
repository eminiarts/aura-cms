<div>
    @section('title', 'Edit Settings • ')

    <div>
        <h1 class="text-3xl font-semibold">Theme Options</h1>
        <h3>Configure Logo and Theme. These Settings are applied for this team</h3>
    </div>

    @php
    // ray($this->fieldsForView)
    @endphp
    @foreach($this->fieldsForView as $key => $field)
    <style >
        #resource-field-{{ optional($field)['slug'] }}-wrapper {
            width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
        }

        @media screen and (max-width: 768px) {
            #resource-field-{{ optional($field)['slug'] }}-wrapper {
                width: 100%;
            }
        }
    </style>
    @checkCondition($model, $field, $form)
        <div wire:key="resource-field-{{ $key }}"
        id="resource-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->component" :field="$field" :form="$form" />
        </div>
        @endcheckCondition
    @endforeach

    <x-aura::button size="xl" wire:click="save">
        <div wire:loading>
            <x-aura::icon.loading  />
        </div>
        {{ __('Save') }}
    </x-aura::button>

</div>
