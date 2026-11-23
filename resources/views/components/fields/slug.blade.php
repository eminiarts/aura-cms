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
            <x-aura::input.text type="text" x-bind:disabled="!custom" id="slug" @keyup="value = slugifyTyping($event.target.value)" x-model="value" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"/>
        </div>

        @if(optional($field)['custom'])
        <div class="flex flex-col custom-slug">
            <button x-ref="toggle" @click="toggleCustom()" type="button" role="switch" :aria-checked="custom ? 'true' : 'false'"
                :aria-labelledby="$id('boolean')"
                class="aura-toggle relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                <span :class="custom ? 'translate-x-[22px]' : 'translate-x-0.5'"
                    class="pointer-events-none inline-block size-5 rounded-full bg-white shadow-sm ring-1 ring-gray-950/10 transition-transform duration-150" aria-hidden="true"></span>
            </button>
        </div>
        @endif
    </div>

</x-aura::fields.wrapper>
