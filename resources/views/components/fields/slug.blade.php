@php
    // if based_on is not set, throw an error
    if (! optional($field)['based_on']) {
        throw new Exception("The slug field needs a 'based_on' property to work.");
    }
@endphp

<x-aura::fields.wrapper :field="$field">
    <div
        x-data="{
            @if(optional($field)['live'] === true)
            value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}').live,
            @else
            value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
            @endif
            custom: @js(! optional($field)['disabled'] ?? false),

            slugify (value) {
                return value
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim()
                    .replace(/[\s\W-]+/g, '_')
                    .replace(/&/g, '_and_')
            },

            slugifyTyping (value) {
                return value
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[\s\W-]+/g, '_')
                    .trim()
                    .replace(/&/g, '_and_')
            },

            toggleCustom() {
                this.custom = ! this.custom;

                if (!this.custom) {
                    const basedOn = document.getElementById('resource-field-{{ optional($field)['based_on'] }}')
                    this.value = this.slugify(basedOn.value);
                }
            },

            init () {
                // get the element of id resource-field-based_on
                const basedOn = document.getElementById('resource-field-{{ optional($field)['based_on'] }}')

                if (this.value != this.slugify(basedOn.value) && basedOn.value != '') {
                    this.custom = true
                }

                if (! this.custom) {
                    this.value = this.slugify(basedOn.value)
                    $dispatch('input', { value: this.value });
                } else {
                }
                // watch the element for changes of the value
                basedOn.addEventListener('input', (event) => {
                    // if the custom value is not set, update the value
                    if (! this.custom) {
                        this.value = this.slugify(event.target.value)
                        $dispatch('input', { value: this.value });
                    }
                })

            }

        }"
        class="flex space-x-4"
    >
        <div
            class="flex-1"
            @if(optional($field)['live'] === true)
            wire:model.live="form.fields.{{ optional($field)['slug'] }}"
            @else
            wire:model="form.fields.{{ optional($field)['slug'] }}"
            @endif
        >
            <x-aura::input.text type="text" x-bind:disabled="!custom" id="slug" @keyup="value = slugifyTyping($event.target.value)" x-model="value" />
        </div>

        @if(optional($field)['custom'])
        <div class="custom-slug flex flex-col">
            <button x-ref="toggle" @click="toggleCustom()" type="button" role="switch" :aria-checked="custom"
                :aria-labelledby="$id('boolean')"
                :class="custom ? 'bg-primary-600 border border-primary-900/50 dark:border-gray-900' : 'bg-gray-300 shadow-inner border border-gray-500/30'"
                class="inline-flex relative px-0 py-1 w-14 rounded-full">
                <span :class="custom ? 'bg-white translate-x-6' : 'bg-white translate-x-1'"
                    class="w-6 h-6 rounded-full transition" aria-hidden="true"></span>
            </button>
        </div>
        @endif
    </div>

</x-aura::fields.wrapper>
