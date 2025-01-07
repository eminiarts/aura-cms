
@php
$values = $field['field']->values($field['model']);
@endphp

<x-aura::fields.wrapper :field="$field">

<div
    class="w-64"
    x-data="{
        value: $wire.entangle('form.fields.{{ $field['slug'] }}'),
        items: {{ Js::from($values) }},

        search: null,

        get filteredItems() {

            if (this.search) {
                return this.items.filter(item => item.title.toLowerCase().includes(this.search.toLowerCase()));
            }

            return this.items;
        },

        findItem(id) {
            return this.items.find(item => item.id === id).title;
        },

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
            class="flex relative gap-2 justify-between items-center py-2.5 pr-3 pl-5 w-52 bg-white rounded-md shadow"
        >
            <span x-text="value ? findItem(value) : 'Select User'" class="truncate"></span>

            <!-- Heroicons up/down -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-gray-500 shrink-0"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" /></svg>
        </button>


        <ul
            x-listbox:options
            x-transition.origin.top.right
            x-cloak
            class="absolute left-0 z-10 mt-2 w-auto bg-white rounded-md border divide-y divide-gray-100 shadow-md origin-top-right outline-none border-gray-400/30"
        >
          <li>
            <div>
              {{-- search input --}}
              <input
                x-model="search"
                autofocus
                class="px-4 py-2.5 w-full placeholder-gray-500 text-gray-900 border-none focus:outline-none"
                placeholder="Search..."

            </div>
          </li>
            <template x-for="item in filteredItems" :key="item.id">
                <li
                    x-listbox:option
                    :value="item.id"
                    :class="{
                        'bg-cyan-500/10 text-gray-900': $listboxOption.isActive,
                        'text-gray-600': ! $listboxOption.isActive,
                        'opacity-50 cursor-not-allowed': $listboxOption.isDisabled,
                    }"
                    class="flex gap-2 justify-between items-center px-4 py-2 w-full text-sm transition-colors cursor-default"
                >
                    <div class="flex items-center space-x-2">
                    <img :src="item.avatar" class="w-8 rounded-full">
                    <div>
                      <span x-text="item.name" class="font-semibold"></span>
                      <span x-text="item.title"></span>
                    </div>

                    <span x-show="$listboxOption.isSelected" class="font-semibold text-cyan-600">&check;</span>
                    </div>

                </li>
            </template>
        </ul>
    </div>
</div>


</x-aura::fields.wrapper>
