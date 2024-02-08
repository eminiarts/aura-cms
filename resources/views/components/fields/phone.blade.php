<x-aura::fields.wrapper :field="$field">
    <x-aura::input.phone wire:model="form.fields.{{ optional($field)['slug'] }}" error="form.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input.phone>
</x-aura::fields.wrapper>
