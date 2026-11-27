@php
    // An option's preview is HTML or [view, data] (Settings::sidebarSample()).
    $renderPreview = fn ($preview) => is_array($preview) ? view($preview[0], $preview[1] ?? [])->render() : $preview;
    $hasPreviews = collect(optional($field)['options'])->contains(fn ($option) => is_array($option) && ! empty($option['preview']));
@endphp

<x-aura::fields.wrapper :field="$field">
    {{-- 8px between options, so a choice list keeps the rhythm of the rest of the form (12px with option pictures).
         Phones set options with pictures side by side, three to a row, each picture above its radio and label. --}}
    <div @class(['flex flex-col py-0.5', 'gap-3 max-sm:grid max-sm:grid-cols-3' => $hasPreviews, 'gap-2' => ! $hasPreviews])>
    @foreach(optional($field)['options'] as $key => $option)

        @if (is_array($option))
            @if(optional($field)['live'] === true)
                <x-aura::input.radio
                    wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option['value']"
                    :preview="$renderPreview($option['preview'] ?? null)"
                    :value="$option['key']"
                    :disabled="optional($field)['disabled'] ?? false"
                />
            @else
                <x-aura::input.radio
                    wire:model="form.fields.{{ optional($field)['slug'] }}"
                    name="post_fields_{{ optional($field)['slug'] }}"
                    id="post_fields_{{ optional($field)['slug'] }}"
                    :label="$option['value']"
                    :preview="$renderPreview($option['preview'] ?? null)"
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
