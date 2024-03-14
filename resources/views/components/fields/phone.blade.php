<x-aura::fields.wrapper :field="$field">
    @if(optional($field)['live'] === true)
    <x-aura::input.phone
        wire:model.live="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        id="aura_field_{{ optional($field)['slug'] }}"
        placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
        autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
    ></x-aura::input.phone>
    @else
        <x-aura::input.phone
        wire:model="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        id="aura_field_{{ optional($field)['slug'] }}"
        placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
        autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
    ></x-aura::input.phone>
    @endif
</x-aura::fields.wrapper>
