<x-fields.wrapper :field="$field">
    <x-input.text wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['name'] }}" id="post-field-{{ optional($field)['slug'] }}" type="password"></x-input.text>
</x-fields.wrapper>
