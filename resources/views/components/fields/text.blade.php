<x-fields.wrapper :field="$field">
    <x-input.text :disabled="optional($field)['disabled']" wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['name'] }}" id="post-field-{{ optional($field)['slug'] }}"></x-input.text>
</x-fields.wrapper>
