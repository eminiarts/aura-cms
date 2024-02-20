<x-aura::fields.wrapper :field="$field">
    @if(optional($field)['live'] === true)
    <x-aura::input
        type="datetime"
        wire:model.live="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
        autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
    ></x-aura::input>
    @else
        <x-aura::input
        type="datetime"
        wire:model="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
        autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
    ></x-aura::input>
    @endif
</x-aura::fields.wrapper>
