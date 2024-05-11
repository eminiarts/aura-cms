@php
    if (optional($field)['api']) {
        //  $values = [];
        $values = $field['field']->valuesForApi($field['resource'], $this->form['fields'][$field['slug']] ?? null);
    } else {
        $values = $field['field']->values($field['resource']);
    }

    $disabled = $field['field']->isDisabled($this->form, $field);
@endphp

{{-- @dump($this->form['fields'][$field['slug']], $values) --}}

<div wire:key="belongsto-{{ $field['slug'] }}" class="w-full">
    <x-aura::fields.wrapper :field="$field">

        <div class="w-full" wire:ignore x-data="{
            @if (optional($field)['live'] === true) value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}').live,
        @else
        value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'), @endif
            {{-- value: $wire.entangle('form.fields.{{ $field['slug'] }}'), --}}

            items: {{ Js::from($values) }},

                api: {{ optional($field)['api'] ? 'true' : 'false' }},
                model: {{ Js::from($field['resource']) }},
                field: {{ Js::from($field['type']) }},
                slug: '{{ $field['slug'] }}',
                disabled: @js($disabled),
                csrf: document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),

                search: null,

                get filteredItems() {

                    if (this.search) {
                        return this.items.filter(item => item.title.toLowerCase().includes(this.search.toLowerCase()));
                    }

                    return this.items;
                },

                init() {

                    // Get Values via API Fetch Call to /api/fields/{field}/values and pass this.model and this.slug as params
                    if (this.api) {
                        this.fetchApi();
                    }

                    // Watch this.search and fetch new values, debounce for 500ms
                    this.$watch('search', () => {
                        if (this.api) {
                            this.fetchApi();
                        }
                    }, { debounce: 500 });

                    var button = this.$refs.button;
                    var input = this.$refs.input;

                    button.addEventListener('click', function() {
                        setTimeout(() => {
                            input.focus();
                        }, 100);
                    });
                },

                findItem(id) {

                    {{-- console.log('findItem', id, this.items);
                    console.log('Type of id:', typeof id); --}}
                    if (this.items) {
                        let foundItem = this.items.find(item => item.id == id);

                        return foundItem ? foundItem.title : null;
                    }
                    return null;
                },

                fetchApi() {

                    let currentId = this.value;
                    let vm = this;

                    fetch('/admin/api/fields/values', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'x-CSRF-TOKEN': this.csrf,
                            },
                            body: JSON.stringify({
                                model: this.model,
                                slug: this.slug,
                                field: this.field,
                                search: this.search,
                                id: this.value,
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            vm.items = data;
                            vm.value = currentId;
                        });
                }

        }">
            <div x-listbox x-model="value" class="relative">
                <label x-listbox:label class="sr-only">Select</label>

                <button x-listbox:button :disabled="disabled"
                    x-ref="button"
                    class="
                        shadow-xs border border-gray-500/30 appearance-none px-3 py-2 focus:outline-none w-full ring-gray-900/10 focus:ring focus:border-primary-300 focus:ring-primary-300  focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 disabled:opacity-75 disabled:bg-gray-100 disabled:dark:bg-gray-800 bg-white dark:bg-gray-900 dark:border-gray-700 z-[1]

                        flex items-center justify-between gap-2 rounded-lg relative
                    ">
                    <span x-text="value ? findItem(value) : '{{ __($field['placeholder'] ?? __('Select')) }}'"
                        class="truncate"></span>

                    <!-- Heroicons up/down -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                        class="w-5 h-5 text-gray-500 shrink-0">
                        <path fill-rule="evenodd"
                            d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <ul x-listbox:options x-transition.origin.top.right x-cloak
                    class="overflow-y-auto absolute left-0 z-10 mt-2 w-full max-h-96 bg-white rounded-lg border divide-y shadow-md origin-top-right outline-none dark:bg-gray-900 divide-gray-500/30 dark:divide-gray-700 border-gray-500/30 dark:border-gray-700">
                    <li>
                        <div>
                            {{-- search input --}}
                            <input x-ref="input" x-model.debounce.300ms="search" autofocus
                                class="px-4 py-2.5 w-full placeholder-gray-500 text-gray-900 border-none focus:outline-none dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400 focus:ring-primary-500"
                                placeholder="Search..." />
                        </div>
                    </li>
                    <template x-if="filteredItems.length === 0">
                        <div class="px-4 py-2 text-sm text-gray-500">No items found</div>
                    </template>
                    <template x-for="item in filteredItems" :key="item.id">
                        <li x-listbox:option :value="item.id"
                            :class="{
                                'dark:bg-primary-500/20 dark:hover:bg-primary-500/30 bg-primary-50 hover:bg-primary-100 focus:outline-none': $listboxOption.isSelected,
                                'dark:bg-gray-900 dark:hover:bg-gray-800 bg-white hover:bg-primary-100 focus:outline-none': !$listboxOption.isSelected,
                                'opacity-50 cursor-not-allowed': $listboxOption.isDisabled,
                            }"
                            class="flex gap-2 justify-between items-center px-4 py-2 w-full text-sm transition-colors cursor-default">
                            <div class="flex items-center space-x-2">
                                <div>
                                    <span x-text="item.title" class="font-semibold"></span>
                                </div>

                                <span x-show="$listboxOption.isSelected"
                                    class="font-semibold text-primary-600">&check;</span>
                            </div>

                        </li>
                    </template>
                </ul>
            </div>
        </div>


    </x-aura::fields.wrapper>

</div>
