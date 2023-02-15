<x-aura::fields.wrapper :field="$field">
    <x-aura::input.datetime wire:model.defer="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"></x-aura::input.datetime>
</x-aura::fields.wrapper>
