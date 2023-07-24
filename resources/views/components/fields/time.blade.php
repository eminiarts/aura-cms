<x-aura::fields.wrapper :field="$field">
    <x-aura::input.time wire:model="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"></x-aura::input.time>
</x-aura::fields.wrapper>
