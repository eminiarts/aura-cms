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
            class="relative inline-flex px-0 py-1 rounded-full w-14">
            <span :class="value ? 'bg-white translate-x-6' : 'bg-white translate-x-1'"
                class="w-6 h-6 transition rounded-full" aria-hidden="true"></span>
        </button>
    </div>
</x-aura::fields.wrapper>
