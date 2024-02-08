<x-aura::fields.wrapper :field="$field">
    <x-aura::input.phone wire:model="resource.fields.{{ optional($field)['slug'] }}" error="resource.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input.phone>
</x-aura::fields.wrapper>
