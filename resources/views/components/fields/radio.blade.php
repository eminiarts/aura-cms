@php
@endphp

<x-aura::fields.wrapper :field="$field">
    {{-- 8px between options, so a choice list keeps the rhythm of the rest of the form. --}}
    <div class="flex flex-col gap-2 py-0.5">
    @foreach(optional($field)['options'] as $key => $option)

        @if (is_array($option))
            @if(optional($field)['live'] === true)
                <x-aura::input.radio
                    wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option['value']"
                    :value="$option['key']"
                    :disabled="optional($field)['disabled'] ?? false"
                />
            @else
                <x-aura::input.radio
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option['value']"
                    :value="$option['key']"
                    :disabled="optional($field)['disabled'] ?? false"
                />
            @endif
        @else
            @if(optional($field)['live'] === true)
                <x-aura::input.radio
                    wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option"
                    :value="$key"
                    :disabled="optional($field)['disabled'] ?? false"
                />
            @else
                <x-aura::input.radio
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option"
                    :value="$key"
                    :disabled="optional($field)['disabled'] ?? false"
                />
            @endif
        @endif
    @endforeach
    </div>
</x-aura::fields.wrapper>
