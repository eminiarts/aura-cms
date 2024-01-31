<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text suffix="{{ optional($field)['suffix'] }}" prefix="{{ optional($field)['prefix'] }}" :disabled="$field['field']->isDisabled($this->post, $field)" wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}" id="post-field-{{ optional($field)['slug'] }}"></x-aura::input.text>
</x-aura::fields.wrapper>
