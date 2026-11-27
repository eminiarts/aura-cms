@props([
  'class' => '',
  'model' => false,
  'label' => '',
  'labelAfter' => '',
])

<!-- Toggle -->
<div
    x-data="{
        value: @entangle($attributes->wire('model')).live,
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

    @if($label)
    <label
        @click="$refs.toggle.click(); $refs.toggle.focus()"
        :id="$id('toggle-label')"
        class="text-sm text-gray-700 transition-colors cursor-pointer dark:text-gray-300"
    >
      {{ $label }}
    </label>
    @endif

    <button
        x-ref="toggle"
        @click="toggle()"
        type="button"
        role="switch"
        :aria-checked="value"
        :aria-labelledby="$id('toggle-label')"
        :class="value ? 'bg-primary-600' : 'bg-gray-300 dark:bg-white/15'"
        class="{{ $label ? 'ml-3' : '' }} relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
    >
        <span
            :class="value ? 'translate-x-[1.125rem]' : 'translate-x-0.5'"
            class="pointer-events-none inline-block size-4 rounded-full bg-white shadow-sm ring-1 ring-gray-950/10 transition-transform duration-150"
            aria-hidden="true"
        ></span>
    </button>

    @if($labelAfter)
    <label
        @click="$refs.toggle.click(); $refs.toggle.focus()"
        :id="$id('toggle-label')"
        class="ml-3 text-sm text-gray-700 transition-colors cursor-pointer dark:text-gray-300"
    >
      {{ $labelAfter }}
    </label>
    @endif
</div>
