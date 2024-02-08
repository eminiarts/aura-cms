<x-aura::fields.wrapper :field="$field">

    @foreach($field['options'] as $key => $option)
        <x-aura::input.checkbox wire:model.live="resource.fields.{{ optional($field)['slug'] }}" :name="$field['slug'] . $option" :id="$field['slug'] . $key" :label="$option" :value="$key" />
    @endforeach

</x-aura::fields.wrapper>
