<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text wire:model="form.fields.{{ optional($field)['slug'] }}" error="form.fields.{{ optional($field)['slug'] }}" placeholder="{{ __(optional($field)['placeholder'] ?? optional($field)['name']) }}" id="resource-field-{{ optional($field)['slug'] }}" type="password" data-lpignore="true" autocomplete="{{ optional($field)['autocomplete'] ?? 'new-password' }}"></x-aura::input.text>
</x-aura::fields.wrapper>
