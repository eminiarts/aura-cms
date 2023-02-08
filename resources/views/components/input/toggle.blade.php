@props([
  'class' => '',
  'model' => false,
  'label' => ''
])

<!-- Toggle -->
<div
    x-data="{
        value: @entangle($attributes->wire('model')),
        toggle() {
            if (this.value) {
                this.value = !this.value;
                this.hasConditionalLogic = this.value;
            } else {
                this.value = !this.value;
                this.hasConditionalLogic = this.value;
            }
        }
    }"
    class="flex items-center"
    x-id="['toggle-label']"
>

    <input
        :value="value"
        type="hidden"
    >

    <!-- Label -->
    <label
        @click="$refs.toggle.click(); $refs.toggle.focus()"
        :id="$id('toggle-label')"
        class="text-black transition-colors dark:text-white"
    >
      {{ $label }}
    </label>

    <!-- Button -->
    <button
        x-ref="toggle"
        @click="toggle()"
        type="button"
        role="switch"
        :aria-checked="value"
        :aria-labelledby="$id('toggle-label')"
        :class="value ? 'bg-primary-600 border border-white' : 'bg-gray-300 shadow-inner border border-gray-500/30'"
        class="ml-4 relative w-14 py-1 px-0 inline-flex rounded-full"
    >
        <span
            :class="value ? 'bg-white translate-x-6' : 'bg-white translate-x-1'"
            class="w-6 h-6 rounded-full transition"
            aria-hidden="true"
        ></span>
    </button>
</div>
