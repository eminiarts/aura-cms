@php
$values = app($field['posttype'])->pluck('title', 'id')->map(fn($name, $key) => ['value' => $key, 'label' => $name])->values()->toArray();
@endphp

@once
@push('scripts')

@endpush
@endonce

{{-- @dump($values)
@dump($this->post) --}}

<x-aura::fields.wrapper :field="$field">
<div
    wire:ignore
    class="w-full"
    x-aura::data="{
        value: $wire.entangle('post.fields.{{ $field['slug'] }}').defer,
        items: {{ Js::from($values) }},

        search: null,

        get selectedItems() {
            if (!this.value || this.value.length === 0) {
                return [];
            }
            return this.items.filter(item => this.value.includes(item.value));
        },

        get filteredItems() {

          console.log('value', this.value);

            if (this.search) {
                return [...new Set([...this.items.filter(item => item.label.toLowerCase().includes(this.search.toLowerCase())), ...this.selectedItems])];
            }

            // join this.selectedItems and this.items and remove duplicates
            return [...new Set([...this.items, ...this.selectedItems])];
        },

        findItem(value) {
            return this.items.find(item => item.value === value).label;
        },

    }"
>
    <div
        x-aura::listbox
        multiple
        x-aura::model="value"
        class="relative w-full p-0 bg-transparent border-0"
    >
        <label x-aura::listbox:label class="sr-only">Select Item</label>

        <button
            x-aura::listbox:button
            class="relative flex items-center justify-between w-full px-aura::3 py-2 border border-gray-500/30 rounded-lg shadow-xs appearance-none focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700"
        >
            <template x-aura::if="value && value.length > 0">
                <div class="flex flex-wrap">
                    <template x-for="item in value" :key="item">
                        <div class="inline-flex items-center gap-1 px-aura::2 py-0.5 mr-2 mb-2 rounded-full text-xs font-medium leading-4 bg-primary-100 text-primary-800">
                            <span
                                class=""
                                x-aura::text="findItem(item)"
                            ></span>

                            <!-- Small x svg -->
                            <svg
                                @click="value = value.filter(i => i !== item)""
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="w-4 h-4 -mr-1 cursor-pointer text-primary-300">
                                <path fill-rule="evenodd" d="M5.293 5.293a1 1 0 011.414 0L10 8.586l3.293-3.293a1 1 0 111.414 1.414L11.414 10l3.293 3.293a1 1 0 01-1.414 1.414L10 11.414l-3.293 3.293a1 1 0 01-1.414-1.414L8.586 10 5.293 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </template>
                </div>
            </template>

            <template x-aura::if="!value || value.length == 0">
                <span class="text-gray-400">Select Item</span>
            </template>

            <!-- Heroicons up/down -->
            <div class="shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-gray-500 shrink-0"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" /></svg>
            </div>
        </button>


        <ul
            x-aura::listbox:options
            x-aura::transition.origin.top
            x-aura::cloak
            class="absolute left-0 z-10 w-full mt-2 overflow-y-auto origin-top bg-white border border-gray-500/30 divide-y divide-gray-100 rounded-md shadow-md outline-none dark:border-gray-700 dark:bg-gray-900 dark:divide-gray-800 max-aura::h-64"
        >
{{--          <li>--}}
{{--            <div>--}}
{{--              <input--}}
{{--                x-aura::model="search"--}}
{{--                autofocus--}}
{{--                class="w-full px-aura::4 py-2.5 text-gray-900 placeholder-gray-500 border-none focus:outline-none"--}}
{{--                placeholder="Search..." />--}}
{{--            </div>--}}
{{--          </li>--}}
            <template x-for="item in filteredItems" :key="item.value">
                <li
                    x-aura::listbox:option
                    :value="item.value"
                    :class="{
                        'bg-primary-50 text-gray-900': $listboxOption.isActive,
                        'text-gray-600': ! $listboxOption.isActive,
                        'opacity-50 cursor-not-allowed': $listboxOption.isDisabled,
                    }"
                    class="flex items-center justify-between w-full gap-2 px-aura::4 py-2 text-sm transition-colors cursor-pointer"
                >
                    <div class="flex items-center space-x-aura::2">
                    <div>
                      <span x-aura::text="item.label" class="font-semibold"></span>
                    </div>

                    <span x-aura::show="$listboxOption.isSelected" class="font-semibold text-cyan-600">&check;</span>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</div>


</x-aura::fields.wrapper>
