
@php

if(optional($field)['api']) {
//  $values = [];
    $values = $field['field']->valuesForApi($field['resource'], $this->post['fields'][$field['slug']] ?? null);
} else {
    $values = $field['field']->values($field['resource']);
}

$disabled = $field['field']->isDisabled($this->post, $field);


@endphp

{{-- @dump($this->post['fields'][$field['slug']]) --}}
<div wire:key="belongsto-{{ $field['slug'] }}" class="w-full">
    <x-aura::fields.wrapper :field="$field">


        <div
            class="w-full"
            wire:ignore
            x-data="{
                value: $wire.entangle('post.fields.{{ $field['slug'] }}'),
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


                         console.log('filtered items');
                console.log(this.search);
                console.log(this.items);
                console.log(this.items.filter(item => item.title.toLowerCase().includes(this.search.toLowerCase())));

                        return this.items.filter(item => item.title.toLowerCase().includes(this.search.toLowerCase()));
                    }

                    return this.items;
                },

                init() {

                    console.log('init belongsto', this.items);
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


                },

                findItem(id) {
                    if (this.items) {
                        let foundItem = this.items.find(item => item.id === id);
                        return foundItem ? foundItem.title : null;
                    }
                    return null;
                },

                fetchApi() {

                    let currentId = this.value;
                    let vm = this;

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
                            id: this.value,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        vm.items = data;
                        vm.value = currentId;
                        console.log('setting current id', currentId);
                    });
                }

            }"
        >
            <div
                x-listbox
                x-model="value"
                class="relative"
            >
                <label x-listbox:label class="sr-only">Select User</label>

                <button
                    x-listbox:button
                    :disabled="disabled"
                    class="
                        shadow-xs border border-gray-500/30 appearance-none px-3 py-2 focus:outline-none w-full ring-gray-900/10 focus:ring focus:border-primary-300 focus:ring-primary-300  focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 disabled:opacity-75 disabled:bg-gray-100 disabled:dark:bg-gray-800 bg-white dark:bg-gray-900 dark:border-gray-700 z-[1]

                        flex items-center justify-between gap-2 rounded-lg relative
                    "
                >
                    <span x-text="value ? findItem(value) : '{{ __($field['placeholder'] ?? __('Select')) }}'" class="truncate"></span>

                    <!-- Heroicons up/down -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-gray-500 shrink-0"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" /></svg>
                </button>

                <ul
                    x-listbox:options
                    x-transition.origin.top.right
                    x-cloak
                    class="absolute left-0 z-10 w-full mt-2 overflow-y-auto origin-top-right bg-white border divide-y divide-gray-100 rounded-lg shadow-md outline-none border-gray-400/30 max-h-96"
                >
                <li>
                    <div>
                    {{-- search input --}}
                    <input
                        x-model.debounce.500ms="search"
                        autofocus
                        class="w-full px-4 py-2.5 text-gray-900 placeholder-gray-500 border-none focus:outline-none"
                        placeholder="Search..."

                    </div>
                </li>
                    <template x-for="item in filteredItems" :key="item.id">
                        <li
                            x-listbox:option
                            :value="item.id"
                            :class="{
                                'bg-primary-500/10 text-gray-900': $listboxOption.isActive,
                                'text-gray-700': ! $listboxOption.isActive,
                                'opacity-50 cursor-not-allowed': $listboxOption.isDisabled,
                            }"
                            class="flex items-center justify-between w-full gap-2 px-4 py-2 text-sm transition-colors cursor-default"
                        >
                            <div class="flex items-center space-x-2">
                            <div>
                            <span x-text="item.title" class="font-semibold"></span>
                            </div>

                            <span x-show="$listboxOption.isSelected" class="font-semibold text-primary-600">&check;</span>
                            </div>

                        </li>
                    </template>
                </ul>
            </div>
        </div>


    </x-aura::fields.wrapper>

</div>
