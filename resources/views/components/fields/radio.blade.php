@php
// ray($field);
@endphp

<x-aura::fields.wrapper :field="$field">

    @foreach(optional($field)['options'] as $key => $option)

        @if (is_array($option))
            @if(optional($field)['live'] === true)
                <x-aura::input.radio
                    wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option['value']"
                    :value="$option['key']"
                />
            @else
                <x-aura::input.radio
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option['value']"
                    :value="$option['key']"
                />
            @endif
        @else
            @if(optional($field)['live'] === true)
                <x-aura::input.radio
                    wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option"
                    :value="$key"
                />
            @else
                <x-aura::input.radio
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option"
                    :value="$key"
                />
            @endif
        @endif
    @endforeach

</x-aura::fields.wrapper>
