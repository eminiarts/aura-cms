@php
    if(optional($field)['api']) {
        $values = [];
        $selectedValues = $field['field']->selectedValues($field['posttype'], $this->post['fields'][$field['slug']]);
    } else {
        // $values = $field['field']->values($field['model']);
        $values = $field['field']->values($field['posttype']);
        $selectedValues = [];
    }
@endphp

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
        loading: false,
        init() {
            if (this.api) {
                this.fetchApi();
            }

            // Watch this.search and fetch new values, debounce for 500ms
            this.$watch('search', () => {
                if (this.api) {
                    this.fetchApi();
                }
            }, { debounce: 500 });
        },
        fetchApi() {
            this.loading = true;

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
                this.loading = false;
            });
        },
        get filteredItems() {
            var items = this.items;
            
            if (this.search) {
                return items.filter(item => item.title.toLowerCase().includes(this.search.toLowerCase()));
            }
            
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
            return false;
        },
        isSelected(item) {
            return this.value.includes(item.id);
        },

        toggleListbox() {
            this.showListbox = !this.showListbox;
        },
        toggleItem(item) {
            if (this.isSelected(item)) {

                this.value = this.value.filter(i => i !== item.id);

                this.$nextTick(() => {
                    this.selectedItems = this.selectedItems.filter(i => i.id !== item.id);
                });


            } else {

                console.log('toggleItem else', this.selectedItems, item.id);
                if(this.selectedItems.length === 0) {
                    this.selectedItems.push(item);
                } else if (!this.selectedItems.find(i => i.id === item.id)) {
                    this.selectedItems.push(item);
                }

                this.$nextTick(() => {
                    this.value.push(item.id);
                });
            }
        },
        selectedItem(id) {
            // only search if this.items is not empty
            if (this.selectedItems.length === 0) {
                return;
            }

            if (!this.selectedItems) {
                return;
            }

            return this.selectedItems.find(item => item.id === id).title;
        },
        focusNext(e) {
            const items = this.$refs.listbox.querySelectorAll(`[role='option']`);
            const active = e.target;

            if (!active) {
                items[0].focus();
                return;
            }

            const index = Array.from(items).indexOf(active);

            if (index === items.length - 1) {
                items[0].focus();
                return;
            }

            items[index + 1].focus();
        },
        focusPrevious(e) {
            const items = this.$refs.listbox.querySelectorAll(`[role='option']`);
            const active = e.target;

            if (!active) {
                items[items.length - 1].focus();
                return;
            }

            const index = Array.from(items).indexOf(active);

            if (index === 0) {
                items[items.length - 1].focus();
                return;
            }

            items[index - 1].focus();
        },
    }"

    @keydown.down.stop.prevent="focusNext"
    @keydown.right.stop.prevent="focusNext"
    @keydown.up.stop.prevent="focusPrevious"
    @keydown.left.stop.prevent="focusPrevious"
    @keydown.escape.stop.prevent="toggleListbox"
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
                                @click.stop.prevent="value = value.filter(i => i !== item)""
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
            x-ref="listbox"
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

          <template x-if="loading">
            <div role="status" class="my-8 mx-auto">
    <svg aria-hidden="true" class="w-8 h-8 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
    </svg>
    <span class="sr-only">Loading...</span>
</div>
          </template>
          <template x-if="!loading">
            <template x-for="item in filteredItems" :key="item.id">
                <li
                    :value="item.id"
                    :class="{
                        'bg-primary-50 hover:bg-primary-100': isActive(item),
                        'bg-white hover:bg-primary-100': ! isSelected(item),
                        'opacity-50 cursor-not-allowed': isDisabled(item),
                    }"
                    class="flex items-center justify-between w-full gap-2 px-4 py-2 text-sm transition-colors cursor-pointer"
                    tabindex="0"
                    role="option"
                    @click="toggleItem(item)"
                    @keydown.enter.stop.prevent="toggleItem(item)"
                    @keydown.space.stop.prevent="toggleItem(item)"
                >
                    <div class="flex items-center space-x-2">
                    <div>
                      <span x-text="item.title" class="font-semibold"></span>
                    </div>

                    <span x-show="isSelected(item)" class="font-semibold text-primary-600">&check;</span>
                    </div>

                </li>
            </template>
            </template>
        </ul>
        </template>
    </div>
</div>
</x-aura::fields.wrapper>
