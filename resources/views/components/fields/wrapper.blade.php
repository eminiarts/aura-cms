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
    $isTranslatable = (bool) optional($field)['translatable'];
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

        @if ($isTranslatable || $help)
            <div class="inline-flex items-center gap-2">
                @if ($isTranslatable)
                    <span class="inline-flex w-9 items-center justify-between align-middle text-gray-400 dark:text-gray-500" aria-label="{{ __('Translatable field') }}" data-translatable-field-indicator>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                        </svg>
                        <span class="text-[10px] font-semibold leading-none tracking-normal" x-text="activeLocale.toUpperCase()"></span>
                    </span>
                @endif

                @if ($help)
                <x-aura::tippy text="{{ $help }}" position="left" class="text-sm text-gray-400 bg-white">
                    <x-aura::icon icon="info" size='sm' />
                </x-aura::tippy>
                @endif
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
