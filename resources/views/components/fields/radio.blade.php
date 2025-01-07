@php
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
                    :disabled="optional($field)['disabled'] ?? false"
                />
            @else
                <x-aura::input.radio
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option['value']"
                    :value="$option['key']"
                    :disabled="optional($field)['disabled'] ?? false"
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
                    :disabled="optional($field)['disabled'] ?? false"
                />
            @else
                <x-aura::input.radio
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option"
                    :value="$key"
                    :disabled="optional($field)['disabled'] ?? false"
                />
            @endif
        @endif
    @endforeach

</x-aura::fields.wrapper>
