@dump(':vendor_name / Skeleton Field')

<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text :disabled="optional($field)['disabled']" wire:model="resource.fields.{{ optional($field)['slug'] }}" error="resource.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" id="post-field-{{ optional($field)['slug'] }}"></x-aura::input.text>
</x-aura::fields.wrapper>