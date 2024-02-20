<x-aura::fields.wrapper :field="$field">
    @if(optional($field)['live'] === true)
        <x-aura::input.text
            :disabled="$field['field']->isDisabled($this->form, $field)"
            suffix="{{ optional($field)['suffix'] }}"
            prefix="{{ optional($field)['prefix'] }}"
            type="number"
            placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
            autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
            wire:model.live="form.fields.{{ optional($field)['slug'] }}"
            error="form.fields.{{ optional($field)['slug'] }}"
        ></x-aura::input.text>
    @else
        <x-aura::input.text
            :disabled="$field['field']->isDisabled($this->form, $field)"
            suffix="{{ optional($field)['suffix'] }}"
            prefix="{{ optional($field)['prefix'] }}"
            type="number"
            placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
            autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
            wire:model="form.fields.{{ optional($field)['slug'] }}"
            error="form.fields.{{ optional($field)['slug'] }}"
        ></x-aura::input.text>
    @endif
</x-aura::fields.wrapper>
