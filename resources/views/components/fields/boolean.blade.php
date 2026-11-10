<x-aura::fields.wrapper :field="$field">
    {{-- The 36x20 switch (same as input.toggle) under its label, as on every other field and with the
         same 6px gap between label and control. --}}
    <div x-data="{
        @if(optional($field)['live'] === true)
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}').live,
        @else
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
        @endif
        disabled: {{ isset($field['disabled']) && $field['disabled'] ? 'true' : 'false' }}
    }"
        class="flex items-center" x-id="['boolean']">
        <button x-ref="toggle" @click="!disabled && (value = !value)" type="button" role="switch" :aria-checked="value"
            :aria-labelledby="$id('boolean')"
            :class="[
                value ? 'bg-primary-600' : 'bg-gray-300 dark:bg-white/15',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
            ]"
            :disabled="disabled"
            class="inline-flex relative items-center w-9 h-5 rounded-full transition-colors duration-150 shrink-0 boolean focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
            <span :class="value ? 'translate-x-[1.125rem]' : 'translate-x-0.5'"
                class="inline-block bg-white rounded-full ring-1 shadow-sm transition-transform duration-150 pointer-events-none size-4 ring-gray-950/10" aria-hidden="true"></span>
        </button>
    </div>
</x-aura::fields.wrapper>
