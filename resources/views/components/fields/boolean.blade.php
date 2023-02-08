<x-aura::fields.wrapper :field="$field">
    <div x-aura::data="{ value: $wire.entangle('post.fields.{{ optional($field)['slug'] }}').defer }"
        class="" x-aura::id="['boolean']">

        <button x-aura::ref="toggle" @click="value = ! value" type="button" role="switch" :aria-checked="value"
            :aria-labelledby="$id('boolean')"
            :class="value ? 'bg-primary-600 border border-white dark:border-gray-900' : 'bg-gray-300 shadow-inner border border-gray-500/30'"
            class="relative inline-flex px-aura::0 py-1 rounded-full w-14">
            <span :class="value ? 'bg-white translate-x-aura::6' : 'bg-white translate-x-aura::1'"
                class="w-6 h-6 transition rounded-full" aria-hidden="true"></span>
        </button>
    </div>

</x-aura::fields.wrapper>
