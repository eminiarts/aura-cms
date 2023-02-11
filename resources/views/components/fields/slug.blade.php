@php
    // if based_on is not set, throw an error
    if (! optional($field)['based_on']) {
        throw new Exception("The slug field needs a 'based_on' property to work.");
    }
@endphp

<x-aura::fields.wrapper :field="$field">
    <div
        x-aura::data="{
            value: $wire.entangle('post.fields.{{ optional($field)['slug'] }}').defer,
            custom: false,

            slugify (value) {
                return value
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim()
                    .replace(/[\s\W-]+/g, '-')
                    .replace(/&/g, '-and-')
            },

            slugifyTyping (value) {
                return value
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[\s\W-]+/g, '-')
                    .trim()
                    .replace(/&/g, '-and-')
            },

            init () {
                // get the element of id post-field-based_on
                const basedOn = document.getElementById('post-field-{{ optional($field)['based_on'] }}')

                console.log('basedOn', basedOn, 'value', this.value, 'post-field-{{ optional($field)['based_on'] }}');

                if (this.value != this.slugify(basedOn.value) && basedOn.value != '') {
                    this.custom = true
                }

                if (! this.custom) {
                    this.value = this.slugify(basedOn.value)
                    $dispatch('input', this.value);
                } else {
                }
                // watch the element for changes of the value
                basedOn.addEventListener('input', (event) => {
                    // if the custom value is not set, update the value
                    console.log('change', event.target.value, this.custom);
                    if (! this.custom) {
                        this.value = this.slugify(event.target.value)
                        $dispatch('input', this.value);
                    }
                })

            }

        }"
        class="flex space-x-aura::4"
    >
        <div class="flex-1" wire:model.defer="post.fields.{{ optional($field)['slug'] }}">
            <x-aura::input.text type="text" x-aura::bind:disabled="!custom" id="slug" @keyup="value = slugifyTyping($event.target.value)" x-aura::model="value" />
        </div>

        <div class="flex flex-col">
            <button x-aura::ref="toggle" @click="custom = ! custom" type="button" role="switch" :aria-checked="custom"
                :aria-labelledby="$id('boolean')"
                :class="custom ? 'bg-primary-600 border border-white dark:border-gray-900' : 'bg-gray-300 shadow-inner border border-gray-500/30'"
                class="relative inline-flex px-aura::0 py-1 rounded-full w-14">
                <span :class="custom ? 'bg-white translate-x-aura::6' : 'bg-white translate-x-aura::1'"
                    class="w-6 h-6 transition rounded-full" aria-hidden="true"></span>
            </button>
        </div>
    </div>

</x-aura::fields.wrapper>



@push('scripts')
    @once
        {{-- <script
            defer
            src="https://unpkg.com/alpinejs-slug@latest/dist/slug.min.js"
        ></script> --}}
    @endonce
@endpush
