<x-aura::fields.wrapper :field="$field">
    <div x-data="{
        @if(optional($field)['live'] === true)
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}').live,
        @else
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
        @endif
    }"
        class="" x-id="['boolean']">
        <button x-ref="toggle" @click="value = ! value" type="button" role="switch" :aria-checked="value"
            :aria-labelledby="$id('boolean')"
            :class="value ? 'bg-primary-600 border border-primary-900/50 dark:border-gray-900' : 'bg-gray-300 shadow-inner border border-gray-500/30'"
            class="inline-flex relative px-0 py-1 w-14 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <span :class="value ? 'bg-white translate-x-6' : 'bg-white translate-x-1'"
                class="w-6 h-6 rounded-full transition" aria-hidden="true"></span>
        </button>
    </div>
</x-aura::fields.wrapper>
