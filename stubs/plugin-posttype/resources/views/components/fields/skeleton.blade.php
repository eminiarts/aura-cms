@dump(':vendor_name / Skeleton Field')

<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text :disabled="optional($field)['disabled']" wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" id="post-field-{{ optional($field)['slug'] }}"></x-aura::input.text>
</x-aura::fields.wrapper>