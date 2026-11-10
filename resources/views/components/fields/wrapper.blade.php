@aware(['mode' => null, 'frame' => null])
@props(['field', 'wrapperClass' => 'px-4', 'showLabel' => true])

@php
    $label = optional($field)['name'];

    if ($label && is_string(__($label))) {
        $label = __($label);
    }

    $required = is_string(optional($field)['validation']) && str(optional($field)['validation'])->contains('required');

    // View pages read like a record: small meta label above a body-sized value.
    $isView = $mode === 'view';

    $help = optional($field)['instructions'];
    $model = 'form.fields.' . $field['slug'];

    $slug = Str::slug(optional($field)['slug']);
    $isTranslatable = (bool) optional($field)['translatable'];
@endphp

<div id="resource-field-{{ $slug }}-wrapper" {{ $attributes->merge(['class' => $wrapperClass]) }}>
    <style>
        #resource-field-{{ $slug }}-wrapper {
            {{-- View pages list label/value rows, one per line; forms keep their column widths, and so does a
                 view in its form's frame (.aura-view-grid), where each value sits where its input sits. --}}
            width: {{ $isView && $frame !== 'form' ? '100' : (optional(optional($field)['style'])['width'] ?? '100') }}%;
        }

        @media screen and (max-width: 768px) {
            #resource-field-{{ $slug }}-wrapper {
                width: 100%;
            }
        }
    </style>

    @if ($isView)
        {{-- Record view: label column on the left, value on the right; stacked on phones. In the form's
             frame .aura-view-grid puts the label above the value. --}}
        <div class="py-2 sm:grid sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-x-6 sm:items-baseline aura-view-row">
            <div class="flex gap-1.5 items-center mb-1 min-w-0 sm:mb-0">
                @if ($label && $showLabel)
                    <span class="text-sm text-gray-500 truncate dark:text-gray-400 aura-view-label">{{ $label }}</span>
                @endif

                {{-- No help marker here: instructions explain how to fill a field, so they belong to the form. --}}

                @if ($isTranslatable)
                    <span class="ml-auto inline-flex w-9 items-center justify-between align-middle text-gray-400 dark:text-gray-500" aria-label="{{ __('Translatable field') }}" data-translatable-field-indicator>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                        </svg>
                        <span class="text-[10px] font-semibold leading-none tracking-normal" x-text="activeLocale.toUpperCase()"></span>
                    </span>
                @endif
            </div>

            <div class="min-w-0 text-sm text-gray-900 dark:text-gray-100">
                @if (isset($slot))
                    {{ $slot }}
                @else
                    <x-aura::input.text :attributes="$attributes"></x-aura::input.text>
                @endif
            </div>
        </div>
    @else
    @if (($label && $showLabel) || $isTranslatable || $help)
    <div class="flex items-center gap-1.5 {{ $isView ? 'mt-3 mb-1' : 'mt-3 mb-1.5' }}">
        @if ($label && $showLabel)
            @if ($isView)
                <span class="aura-eyebrow">{{ $label }}</span>
            @else
                <x-aura::fields.label :label="$label" :required="$required" class="!my-0" />
            @endif
        @endif

        @if ($help)
            {{-- Small inline help marker right after the label, not a heavy icon at the far edge. --}}
            <x-aura::tippy text="{{ $help }}" position="top">
                <x-aura::icon icon="info" class="flex" iconClass="h-3.5 w-3.5 text-gray-400 transition-colors duration-150 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300" />
            </x-aura::tippy>
        @endif

        @if ($isTranslatable)
            <span class="ml-auto inline-flex w-9 items-center justify-between align-middle text-gray-400 dark:text-gray-500" aria-label="{{ __('Translatable field') }}" data-translatable-field-indicator>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                </svg>
                <span class="text-[10px] font-semibold leading-none tracking-normal" x-text="activeLocale.toUpperCase()"></span>
            </span>
        @endif
    </div>
    @endif

    <div class="">
        @if (isset($slot))
            {{ $slot }}
        @else
            <x-aura::input.text :attributes="$attributes"></x-aura::input.text>
        @endif

        @if ($model && $errors->has($model))
            <span class="mt-1 block text-sm text-red-600 dark:text-red-400 error">{{ $errors->first($model) }}</span>
        @endif

    </div>
    @endif
</div>
