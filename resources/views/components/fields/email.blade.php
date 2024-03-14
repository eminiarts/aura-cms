<x-aura::fields.wrapper :field="$field">
    @if(optional($field)['live'] === true)
        <x-aura::input.email
            wire:model.live="form.fields.{{ optional($field)['slug'] }}"
            error="form.fields.{{ optional($field)['slug'] }}"
            placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
            id="aura_field_{{ optional($field)['slug'] }}"
            autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
        ></x-aura::input.email>
    @else
        <x-aura::input.email
        wire:model="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
        id="aura_field_{{ optional($field)['slug'] }}"
        autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
    ></x-aura::input.email>
    @endif
</x-aura::fields.wrapper>
