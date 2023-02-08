<x-fields.wrapper :field="$field">

    @foreach($field['options'] as $key => $option)
        <x-input.checkbox wire:model.defer="post.fields.{{ optional($field)['slug'] }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" :label="$option" :value="$key" />
    @endforeach

</x-fields.wrapper>
