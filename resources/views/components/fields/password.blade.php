<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text wire:model="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ __(optional($field)['placeholder'] ?? optional($field)['name']) }}" id="post-field-{{ optional($field)['slug'] }}" type="password" autocomplete="off" data-lpignore="true" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input.text>
</x-aura::fields.wrapper>
