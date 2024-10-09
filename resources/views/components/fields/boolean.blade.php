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
        <button x-ref="toggle" @click="!disabled && (value = !value)" type="button" role="switch" :aria-checked="value"
            :aria-labelledby="$id('boolean')"
            :class="[
                value ? 'bg-primary-600 border border-primary-900/50 dark:border-gray-900' : 'bg-gray-300 shadow-inner border border-gray-500/30 dark:bg-gray-800',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
            ]"
            :disabled="disabled"
            class="inline-flex relative px-0 py-1 w-14 rounded-full boolean focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <span :class="[
                value ? 'bg-white translate-x-6' : 'bg-white translate-x-1',
                disabled ? 'bg-white' : ''
            ]"
                class="w-6 h-6 rounded-full transition" aria-hidden="true"></span>
        </button>
    </div>
</x-aura::fields.wrapper>
