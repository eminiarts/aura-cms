<x-fields.wrapper :field="$field">
    <x-input.time wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['name'] }}"></x-input.time>
</x-fields.wrapper>
