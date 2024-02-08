<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text wire:model="resource.fields.{{ optional($field)['slug'] }}" error="resource.fields.{{ optional($field)['slug'] }}" placeholder="{{ __(optional($field)['placeholder'] ?? optional($field)['name']) }}" id="post-field-{{ optional($field)['slug'] }}" type="password" data-lpignore="true" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input.text>
</x-aura::fields.wrapper>
