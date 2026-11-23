<x-aura::fields.wrapper :field="$field">
    <div x-data="{
        @if(optional($field)['live'] === true)
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}').live,
        @else
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
        @endif
        disabled: {{ isset($field['disabled']) && $field['disabled'] ? 'true' : 'false' }}
    }"
        class="" x-id="['boolean']">
        <button x-ref="toggle" @click="!disabled && (value = !value)" type="button" role="switch" :aria-checked="value ? 'true' : 'false'"
            :aria-labelledby="$id('boolean')"
            :class="disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
            :disabled="disabled"
            class="aura-toggle boolean relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
            <span :class="value ? 'translate-x-[22px]' : 'translate-x-0.5'"
                class="pointer-events-none inline-block size-5 rounded-full bg-white shadow-sm ring-1 ring-gray-950/10 transition-transform duration-150" aria-hidden="true"></span>
        </button>
    </div>
</x-aura::fields.wrapper>
