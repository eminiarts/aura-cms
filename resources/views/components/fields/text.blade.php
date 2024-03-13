<x-aura::fields.wrapper :field="$field">

    @if(optional($field)['live'] === true)
        <x-aura::input.text
            suffix="{{ optional($field)['suffix'] }}"
            prefix="{{ optional($field)['prefix'] }}"
            :disabled="$field['field']->isDisabled($form, $field)"
            wire:model.live="form.fields.{{ optional($field)['slug'] }}"
            error="form.fields.{{ optional($field)['slug'] }}"
            placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
            id="resource-field-{{ optional($field)['slug'] }}"
            autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
        ></x-aura::input.text>
    @else
        <x-aura::input.text
            suffix="{{ optional($field)['suffix'] }}"
            prefix="{{ optional($field)['prefix'] }}"
            :disabled="$field['field']->isDisabled($form, $field)"
            wire:model="form.fields.{{ optional($field)['slug'] }}"
            error="form.fields.{{ optional($field)['slug'] }}"
            placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
            id="resource-field-{{ optional($field)['slug'] }}"
            autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
        ></x-aura::input.text>
    @endif

</x-aura::fields.wrapper>
