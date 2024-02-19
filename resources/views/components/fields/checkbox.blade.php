<x-aura::fields.wrapper :field="$field">

    @foreach($field['options'] as $key => $option)
        @if (is_array($option))
        <x-aura::input.checkbox wire:model.live="form.fields.{{ optional($field)['slug'] }}" :name="$field['slug'] . $option['key']" :id="$field['slug'] . $option['key']" :label="$option['value']" :value="$option['key']" />
        @else
        <x-aura::input.checkbox wire:model.live="form.fields.{{ optional($field)['slug'] }}" :name="$field['slug'] . $option" :id="$field['slug'] . $option" :label="$option" :value="$key" />
        @endif
    @endforeach

</x-aura::fields.wrapper>
