@php
    $booleanLabel = optional($field)['name'];
    $booleanLabel = $booleanLabel && is_string(__($booleanLabel)) ? __($booleanLabel) : $booleanLabel;
    $booleanHelp = optional($field)['instructions'];
    $booleanRequired = optional($field)['disabled'] !== true && is_string(optional($field)['validation']) && str(optional($field)['validation'])->contains('required');
@endphp

{{-- A switch reads as a sentence: the 36x20 switch (same as input.toggle) on the left, its label and the
     field's instructions as visible text beside it, both of which toggle it. The wrapper keeps the field's
     error line; its own label row (and the help tooltip) stays away. A switch with instructions takes the
     form's full width, so the sentence is not squeezed into a half column, and the text stops at 560px. --}}
<x-aura::fields.wrapper :field="array_merge($field, ['instructions' => null], $booleanHelp ? ['style' => array_merge((array) (optional($field)['style'] ?? []), ['width' => '100'])] : [])" :show-label="false">
    <div x-data="{
        @if(optional($field)['live'] === true)
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}').live,
        @else
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
        @endif
        disabled: {{ isset($field['disabled']) && $field['disabled'] ? 'true' : 'false' }}
    }"
        class="flex gap-3 items-start mt-3" x-id="['boolean', 'boolean-help']" data-boolean-field>
        <button x-ref="toggle" @click="!disabled && (value = !value)" type="button" role="switch" :aria-checked="value"
            :aria-labelledby="$id('boolean')"
            @if($booleanHelp) :aria-describedby="$id('boolean-help')" @endif
            :class="[
                value ? 'bg-primary-600' : 'bg-gray-300 dark:bg-white/15',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
            ]"
            :disabled="disabled"
            class="inline-flex relative items-center w-9 h-5 rounded-full transition-colors duration-150 shrink-0 boolean focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
            <span :class="value ? 'translate-x-[1.125rem]' : 'translate-x-0.5'"
                class="inline-block bg-white rounded-full ring-1 shadow-sm transition-transform duration-150 pointer-events-none size-4 ring-gray-950/10" aria-hidden="true"></span>
        </button>

        @if($booleanLabel || $booleanHelp)
            <div class="min-w-0 text-sm leading-5" :class="disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'" @click="!disabled && (value = !value)">
                @if($booleanLabel)
                    <span :id="$id('boolean')" class="block font-medium text-gray-700 dark:text-gray-200">{{ $booleanLabel }}@if($booleanRequired)<span class="ml-0.5 text-gray-400 dark:text-gray-500" aria-hidden="true">*</span>@endif</span>
                @endif
                @if($booleanHelp)
                    <span :id="$id('boolean-help')" class="block mt-0.5 max-w-[35rem] text-gray-500 dark:text-gray-400">{{ __($booleanHelp) }}</span>
                @endif
            </div>
        @endif
    </div>
</x-aura::fields.wrapper>
