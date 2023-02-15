<x-aura::fields.wrapper :field="$field">
    <x-aura::input.phone wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"></x-aura::input.phone>
</x-aura::fields.wrapper>
