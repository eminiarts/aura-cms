
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

            {{-- Selector mark --}}
            <x-aura::icon.caret direction="up-down" class="w-4 h-4 text-gray-500 shrink-0 dark:text-gray-400" />
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
                placeholder="Search…"

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
