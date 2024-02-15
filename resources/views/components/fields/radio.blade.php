<x-aura::fields.wrapper :field="$field">
@php
ray($field);
@endphp
    @foreach(optional($field)['options'] as $key => $option)
        <x-aura::input.radio wire:model="form.fields.{{ optional($field)['slug'] }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" :label="$option['name']" :value="$option['value']" />
    @endforeach

</x-aura::fields.wrapper>
