<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text suffix="{{ optional($field)['suffix'] }}" prefix="{{ optional($field)['prefix'] }}" :disabled="$field['field']->isDisabled($this->resource, $field)" wire:model="resource.fields.{{ optional($field)['slug'] }}" error="resource.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" id="resource-field-{{ optional($field)['slug'] }}" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input.text>
</x-aura::fields.wrapper>

{{-- @dump(data_get($this->resource['fields'], $field['slug'])); --}}
