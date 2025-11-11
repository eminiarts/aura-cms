@php
// Initialize checkbox field value if null
$formData = (isset($this) && isset($this->form)) ? $this->form : ($form ?? []);
if (is_null(data_get($formData, "fields.{$field['slug']}"))) {
    if (isset($this) && isset($this->form)) {
        $this->form['fields'][$field['slug']] = [];
    }
    // Note: For standalone components, we can't modify the form data
}
@endphp
<x-aura::fields.wrapper :field="$field">

    @foreach($field['options'] as $key => $option)

        @if (is_array($option))
            @if(optional($field)['live'] === true)
                <x-aura::input.checkbox
                    wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                    :name="$field['slug'] . '[' . $key . ']'"
                    :id="$field['slug'] . $option['key']"
                    :label="$option['value']"
                    :value="$option['key']"
                    :disabled="$field['disabled'] ?? false"
                />
            @else
                <x-aura::input.checkbox
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    :name="$field['slug'] . '[' . $key . ']'"
                    :id="$field['slug'] . $option['key']"
                    :label="$option['value']"
                    :value="$option['key']"
                    :disabled="$field['disabled'] ?? false"
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
                    :disabled="$field['disabled'] ?? false"
                />
            @else
                <x-aura::input.checkbox
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    :name="$field['slug'] . '[' . $option . ']'"
                    :id="$field['slug'] . $option"
                    :label="$option"
                    :value="$key"
                    :disabled="$field['disabled'] ?? false"
                />
            @endif
        @endif

    @endforeach

</x-aura::fields.wrapper>
