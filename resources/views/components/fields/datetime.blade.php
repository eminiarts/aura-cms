<x-fields.wrapper :field="$field">
    <x-input.datetime wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['name'] }}"></x-input.datetime>
</x-fields.wrapper>
