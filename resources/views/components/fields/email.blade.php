<x-aura::fields.wrapper :field="$field">
    <x-aura::input.email wire:model="resource.fields.{{ optional($field)['slug'] }}" error="resource.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input.email>
</x-aura::fields.wrapper>
