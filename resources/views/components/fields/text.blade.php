<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text suffix="{{ optional($field)['suffix'] }}" prefix="{{ optional($field)['prefix'] }}" :disabled="$field['field']->isDisabled($this->post, $field)" wire:model="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" id="post-field-{{ optional($field)['slug'] }}" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input.text>
</x-aura::fields.wrapper>

{{-- @dump(data_get($this->post['fields'], $field['slug'])); --}}
