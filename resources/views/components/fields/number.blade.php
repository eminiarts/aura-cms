<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text
        :disabled="optional($field)['disabled']"
        suffix="{{ optional($field)['suffix'] }}"
        prefix="{{ optional($field)['prefix'] }}"
        type="number"
        wire:model="post.fields.{{ optional($field)['slug'] }}"
        error="post.fields.{{ optional($field)['slug'] }}"
    ></x-aura::input.text>
</x-aura::fields.wrapper>
