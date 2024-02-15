<x-aura::fields.wrapper :field="$field">

    @foreach($field['options'] as $key => $option)
        <x-aura::input.checkbox wire:model.live="form.fields.{{ optional($field)['slug'] }}" :name="$field['slug'] . $option['value']" :id="$field['slug'] . $option['value']" :label="$option['name']" :value="$option['value']" />
    @endforeach

</x-aura::fields.wrapper>
