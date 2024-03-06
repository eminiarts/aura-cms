<x-aura::fields.wrapper :field="$field">

@php
if($this->form['fields'][$field['slug']] === null) {
    $this->form['fields'][$field['slug']] = [];
}
@endphp

    @foreach($field['options'] as $key => $option)

        @if (is_array($option))
            @if(optional($field)['live'] === true)
                <x-aura::input.checkbox
                    wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                    :name="$field['slug'] . '[' . $key . ']'"
                    :id="$field['slug'] . $option['key']"
                    :label="$option['value']"
                    :value="$option['key']"
                />
            @else
                <x-aura::input.checkbox
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    :name="$field['slug'] . '[' . $key . ']'"
                    :id="$field['slug'] . $option['key']"
                    :label="$option['value']"
                    :value="$option['key']"
                />
            @endif
        @else
            @if(optional($field)['live'] === true)
                <x-aura::input.checkbox
                    wire:model.live="form.fields.{{ optional($field)['slug'] }}"

                    :name="$field['slug'] . '[' . $option . ']'"
                    :id="$field['slug'] . $option"
                    :label="$option"
                    :value="$key"
                />
            @else
                <x-aura::input.checkbox
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    :name="$field['slug'] . '[' . $option . ']'"
                    :id="$field['slug'] . $option"
                    :label="$option"
                    :value="$key"
                />
            @endif
        @endif


    @endforeach

</x-aura::fields.wrapper>
