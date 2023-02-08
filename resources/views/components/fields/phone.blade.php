<x-fields.wrapper :field="$field">
    <x-input.phone wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['name'] }}"></x-input.phone>
</x-fields.wrapper>
