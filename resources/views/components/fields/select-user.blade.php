
@php

dd($field['field']->values($field['model']), $field);

// Maybe set a custom display field for BelongsTo fields, eg. email instead of Title
// $displayField = $field['field']->displayField ?? 'title';

$values = $field['field']->values($field['model']);

// Paginate the results

// For Users
// $values = $field['field']->attributes['values']()->map(function($user) {
//   return [
//     'id' => $user->id,
//     'name' => $user->name,
//     'email' => $user->email,
//     'avatar' => 'https://i.pravatar.cc/300'
//   ];
// })->toArray();

@endphp

{{-- @dump($field['slug']) --}}
{{-- @dump($this->post['fields'][$field['slug']]) --}}

@once
@push('scripts')
@endpush
@endonce

<x-aura::fields.wrapper :field="$field">

<div
    class="w-64"
    x-aura::data="{
        value: $wire.entangle('post.fields.{{ $field['slug'] }}').defer,
        items: {{ Js::from($values) }},

        search: null,

        get filteredItems() {

          console.log(this.search);

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
        x-aura::listbox
        x-aura::model="value"
        class="relative"
    >
        <label x-aura::listbox:label class="sr-only">Select User</label>

        <button
            x-aura::listbox:button
            class="flex items-center justify-between gap-2 w-52 bg-white pl-5 pr-3 py-2.5 rounded-md shadow relative"
        >
            <span x-aura::text="value ? findItem(value) : 'Select User'" class="truncate"></span>

            <!-- Heroicons up/down -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-gray-500 shrink-0"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" /></svg>
        </button>


        <ul
            x-aura::listbox:options
            x-aura::transition.origin.top.right
            x-aura::cloak
            class="absolute left-0 z-10 w-auto mt-2 origin-top-right bg-white border border-gray-400/30 divide-y divide-gray-100 rounded-md shadow-md outline-none"
        >
          <li>
            <div>
              {{-- search input --}}
              <input
                x-aura::model="search"
                autofocus
                class="w-full px-aura::4 py-2.5 text-gray-900 placeholder-gray-500 border-none focus:outline-none"
                placeholder="Search..."

            </div>
          </li>
            <template x-aura::for="item in filteredItems" :key="item.id">
                <li
                    x-aura::listbox:option
                    :value="item.id"
                    :class="{
                        'bg-cyan-500/10 text-gray-900': $listboxOption.isActive,
                        'text-gray-600': ! $listboxOption.isActive,
                        'opacity-50 cursor-not-allowed': $listboxOption.isDisabled,
                    }"
                    class="flex items-center justify-between w-full gap-2 px-aura::4 py-2 text-sm transition-colors cursor-default"
                >
                    <div class="flex items-center space-x-aura::2">
                    <img :src="item.avatar" class="w-8 rounded-full">
                    <div>
                      <span x-aura::text="item.name" class="font-semibold"></span>
                      <span x-aura::text="item.title"></span>
                    </div>

                    <span x-aura::show="$listboxOption.isSelected" class="font-semibold text-cyan-600">&check;</span>
                    </div>

                </li>
            </template>
        </ul>
    </div>
</div>


</x-aura::fields.wrapper>
