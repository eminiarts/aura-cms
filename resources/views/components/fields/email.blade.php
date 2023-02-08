<x-fields.wrapper :field="$field">
    <x-input.email wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['name'] }}"></x-input.email>
</x-fields.wrapper>
