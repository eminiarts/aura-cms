<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text
        :disabled="$field['field']->isDisabled($this->resource, $field)"
        suffix="{{ optional($field)['suffix'] }}"
        prefix="{{ optional($field)['prefix'] }}"
        type="number"
        placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
        autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
        wire:model="resource.fields.{{ optional($field)['slug'] }}"
        error="resource.fields.{{ optional($field)['slug'] }}"
    ></x-aura::input.text>
</x-aura::fields.wrapper>
