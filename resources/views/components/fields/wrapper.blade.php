@props(['field', 'wrapperClass' => 'px-4', 'showLabel' => true])

@php
    $label = optional($field)['name'];

    if ($label && is_string(__($label))) {
        $label = __($label);
    }

    // if the field is required, add a * to the label
    if (is_string(optional($field)['validation']) && str(optional($field)['validation'])->contains('required')) {
        $label .= '*';
    }

    $help = optional($field)['instructions'];
    $model = 'form.fields.' . $field['slug'];

    $slug = Str::slug(optional($field)['slug']);
@endphp

<div id="resource-field-{{ $slug }}-wrapper" {{ $attributes->merge(['class' => $wrapperClass]) }}>
    <style>
        #resource-field-{{ $slug }}-wrapper {
            width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
        }

        @media screen and (max-width: 768px) {
            #resource-field-{{ $slug }}-wrapper {
                width: 100%;
            }
        }
    </style>
    <div class="flex justify-between items-center">
        @if ($label && $showLabel)
            <x-aura::fields.label :label="$label" />
        @endif

        @if ($help)
            <div class="text-gray-300">
                <x-aura::tippy text="{{ $help }}" position="left" class="text-sm text-gray-400 bg-white">
                    <x-aura::icon icon="info" size='sm' />
                </x-aura::tippy>
            </div>
        @endif
    </div>

    <div class="">
        @if (isset($slot))
            {{ $slot }}
        @else
            @dump('NO SLOT DEFINED')
            <x-aura::input.text :attributes="$attributes"></x-aura::input.text>
        @endif

        @if ($model && $errors->has($model))
            <span class="text-sm font-semibold text-red-500 error">{{ $errors->first($model) }}</span>
        @endif

    </div>
</div>
