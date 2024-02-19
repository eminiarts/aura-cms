@php
// ray($field);
@endphp

<x-aura::fields.wrapper :field="$field">

    @foreach(optional($field)['options'] as $key => $option)

        @if (is_array($option))
            <x-aura::input.radio wire:model="form.fields.{{ optional($field)['slug'] }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" :label="$option['value']" :value="$option['key']" />
        @else
            <x-aura::input.radio wire:model="form.fields.{{ optional($field)['slug'] }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" :label="$option" :value="$key" />
        @endif
    @endforeach

</x-aura::fields.wrapper>
