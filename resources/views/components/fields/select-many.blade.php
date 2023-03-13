@php

if(optional($field)['api']) {
    $values = [];

    // selected values
    $selectedValues = $field['field']->selectedValues($field['posttype'], $this->post['fields'][$field['slug']]);
} else {
    // $values = $field['field']->values($field['model']);
    $values = $field['field']->values($field['posttype']);
}

@endphp

{{-- @dump($values)
@dump($field)
@dump($selectedValues) --}}
@dump($this->post['fields'][$field['slug']])

<x-aura::fields.wrapper :field="$field">
<div
    wire:ignore
    class="w-full"
    x-data="{
        value: $wire.entangle('post.fields.{{ $field['slug'] }}').defer,
        items: {{ Js::from($values) }},
        selectedItems: {{ Js::from($selectedValues) }},
        api: {{ optional($field)['api'] ? 'true' : 'false' }},
        model: {{ Js::from($field['posttype']) }},
        field: {{ Js::from($field['type']) }},
        slug: '{{ $field['slug'] }}',
        csrf: document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
        search: null,
        showListbox: false,
        loaded: false,

         init() {

            console.log(this.value, 'initial value');
            
                if (this.api) {
                 this.fetchApi();
                }

            // Watch this.search and fetch new values, debounce for 500ms
            this.$watch('search', () => {
                if (this.api) {
                    this.fetchApi();
                }
            }, { debounce: 500 });

            this.$watch('value', () => {
                this.$nextTick(() => {
                   console.log('value changed', this.value);

                this.value.forEach(value => {
                    if (!this.selectedItems.find(item => item.id === value)) {
                        this.selectedItems.push(this.items.find(item => item.id === value));
                    }
                });

                console.log(this.selectedItems);
                });

            });


        },

        fetchApi() {
            
            fetch('/admin/api/fields/values', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'x-CSRF-TOKEN': this.csrf,
                },
                body: JSON.stringify({
                    model: this.model,
                    slug: this.slug,
                    field: this.field,
                    search: this.search,
                }),
            })
            .then(response => response.json())
            .then(data => {
                this.items = data;
                this.loaded = true;

                this.$nextTick(() => {
                    {{-- this.$refs.listbox.init(); --}}
                    {{-- this.$refs.listbox.dispatchEvent(new Event('alpine:init')); --}}
                });

                // refresh the alpinejs component
                
            });
        },

        get filteredItems() {

            
            // merge selectedItems with items
            var items = this.items;
            
            if (this.search) {
                items = items.filter(item => item.title.toLowerCase().includes(this.search.toLowerCase()));
            }
            
            console.log('search here');
            console.log(this.search, items);

            // if this.items length is 0, return selectedItems
            if (items.length === 0) {
                return this.selectedItems;
            }

            // return this.selectedItems and items and remove duplicates by id
            return [...this.selectedItems, ...items].filter((item, index, self) => self.findIndex(i => i.id === item.id) === index).sort((a, b) => a.id - b.id);

            
        },

        isActive(item) {
            return this.value.includes(item.id);
        },
        isDisabled(item) {
            return this.value.length >= 5 && !this.isActive(item);
        },
        isSelected(item) {
            return this.value.includes(item.id);
        },

        toggleListbox() {
            this.showListbox = !this.showListbox;
        },

        selectedItem(id) {
            // only search if this.items is not empty
            if (this.selectedItems.length === 0) {
                return;
            }

            console.log('selectedItem', id, this.selectedItems, this.selectedItems.find(item => item.id === id).title);

            return this.selectedItems.find(item => item.id === id).title;
        },

    }"
>


    <div class="relative w-full p-0 bg-transparent border-0 aura-input">
        <label class="sr-only">Select Item</label>

        <button
            class="relative flex items-center justify-between w-full px-3 py-2 border border-gray-500/30 rounded-lg shadow-xs appearance-none focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700"
            @click="toggleListbox"
        >
            <template x-if="value && value.length > 0">
                <div class="flex flex-wrap">
                    <template x-for="item in value" :key="item">
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 mr-2 mb-2 rounded-full text-xs font-medium leading-4 bg-primary-100 text-primary-800">
                            <span
                                class=""
                                x-text="selectedItem(item)"
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

            <template x-if="!value || value.length == 0">
                <span class="text-gray-400">Select Item</span>
            </template>

            <!-- Heroicons up/down -->
            <div class="shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-gray-500 shrink-0"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" /></svg>
            </div>
        </button>

        <template x-if="showListbox">
        <ul
            x-transition.origin.top
            x-cloak
            class="absolute left-0 z-10 w-full mt-2 overflow-y-auto origin-top bg-white border border-gray-500/30 divide-y divide-gray-100 rounded-md shadow-md outline-none dark:border-gray-700 dark:bg-gray-900 dark:divide-gray-800 max-h-64"
        >
     <li>
            <div>
              <input
                x-model.debounce.500ms="search"
                autofocus
                class="w-full px-4 py-2.5 text-gray-900 placeholder-gray-500 border-none focus:outline-none"
                placeholder="Search...">
            </div>
          </li>
            <template x-for="item in filteredItems" :key="item.id">
                <li
                    :value="item.id"
                    :class="{
                        'bg-primary-500 hover:bg-primary-100': isActive(item.id),
                        'bg-primary-500 hover:bg-primary-100': ! isActive(item.id),
                        'opacity-50 cursor-not-allowed': isDisabled(item.id),
                    }"
                    class="flex items-center justify-between w-full gap-2 px-4 py-2 text-sm transition-colors cursor-default"
                >
                    <div class="flex items-center space-x-2">
                    <div>
                      <span x-text="item.title" class="font-semibold"></span>
                    </div>

                    <span x-show="isSelected(item.id)" class="font-semibold text-primary-600">&check;</span>
                    </div>

                </li>
            </template>
        </ul>
        </template>
    </div>
</div>


</x-aura::fields.wrapper>
