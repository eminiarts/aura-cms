@aware(['model'])
@php
    $name = 'form.fields.'  . optional($field)['slug'];

    $groups = app($field['resource'])->get()->map(function($item, $key) {
        return [
            'name' => $item->fields['name'],
            'description' => $item->fields['description'],
            'slug' => $item->fields['slug'],
            'group' => $item->group,
        ];
    })->groupBy('group')->toArray();

@endphp

<x-aura::fields.conditions :field="$field" :model="$model">
<div class="w-full" x-data="{
    groups: @js($groups),
    field: @entangle($name),
    init(){

        if (this.field === null) {
            this.field = {};
        }

        // if this.field is null, set it to an empty array
        if (Array.isArray(this.field) && this.field.length === 0) {
            this.field = {};
        }

        // Set the initial value of the field to false, if not set
        for (const [key, value] of Object.entries(this.groups)) {
            for (const [key2, value2] of Object.entries(value)) {
                if (this.field[value2['slug']] === undefined) {
                    this.field[value2['slug']] = false;
                }
            }
        }

        // Remove any keys from this.field that are not in the groups items (item.slug)
        {{-- for (const [key, value] of Object.entries(this.field)) {
            if (!this.groups.flat().map(item => item.slug).includes(key)) {
                delete this.field[key];
            }
        } --}}
    },
    selectAll(group){
        this.groups[group].forEach(item => {
            if (item.slug.includes('scope')) {
                return;
            }

            this.field[item.slug] = true;
        });
    },
    selectAllGroups(){
        for (const [key, value] of Object.entries(this.groups)) {
            this.selectAll(key);
        }
    },
    selectedCount(group){
        return this.groups[group].filter(item => Boolean(this.field[item.slug])).length;
    },
    removeAll(group){
        this.groups[group].forEach(item => {
            this.field[item.slug] = false;
        });
    },
    removeAllGroups(){
        for (const [key, value] of Object.entries(this.groups)) {
            this.removeAll(key);
        }
    },
}">
<style>
    .aura-permissions-groups {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(18rem, 100%), 1fr));
        align-items: start;
        gap: 0.75rem;
    }

    .aura-permissions-options {
        display: flex;
        flex-direction: column;
        row-gap: 0;
    }
</style>
<x-aura::fields.wrapper :field="$field">
    <div class="flex items-center justify-end gap-1 mb-3">
        <button type="button" @click="removeAllGroups()" class="px-3 py-1.5 text-xs font-medium text-gray-500 transition-colors rounded-md hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100">
            {{ __('Clear all') }}
        </button>
        <button type="button" @click="selectAllGroups()" class="px-3 py-1.5 text-xs font-medium transition-colors rounded-md text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-500/10 dark:hover:bg-primary-500/20">
            {{ __('Select all permissions') }}
        </button>
    </div>

    <div class="aura-permissions-groups">
        @foreach($groups as $group => $items)
            <section class="overflow-hidden border rounded-lg border-gray-500/20 dark:border-gray-700">
                <div class="flex items-start justify-between min-w-0 gap-3 px-4 pt-4 pb-2">
                    <div class="min-w-0">
                        <h4 class="min-w-0 text-sm font-semibold leading-5 text-gray-900 break-words dark:text-gray-100">{{ $group }}</h4>
                        <span class="block mt-0.5 text-[11px] font-medium text-gray-400 dark:text-gray-500">
                            <span x-text="selectedCount('{{ $group }}')"></span> / <span>{{ count($items) }}</span>
                        </span>
                    </div>

                    <div class="flex items-center pt-0.5 text-xs shrink-0">
                        <button type="button" @click="selectAll('{{ $group }}')" class="font-medium text-gray-500 transition-colors whitespace-nowrap hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">
                            {{ __('Select all') }}
                        </button>
                        <button type="button" @click="removeAll('{{ $group }}')" class="pl-2 ml-2 font-medium text-gray-500 transition-colors border-l whitespace-nowrap border-gray-300 hover:text-gray-900 dark:text-gray-400 dark:border-gray-700 dark:hover:text-gray-100">
                            {{ __('Clear') }}
                        </button>
                    </div>
                </div>

                <div class="aura-permissions-options min-w-0 px-2 pb-2">
                    @foreach($items as $item)
                        <label for="permissions_{{ $item['slug'] }}" class="flex min-w-0 items-start gap-2.5 px-2 py-1.5 text-sm rounded-md cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/70">
                            <input x-model="field['{{ $item['slug'] }}']" type="checkbox" id="permissions_{{ $item['slug'] }}" class="w-4 h-4 mt-0.5 bg-gray-100 rounded transition duration-150 ease-in-out cursor-pointer shrink-0 border-gray-500/30 form-checkbox text-primary-600 focus:ring-primary-500">
                            <span class="min-w-0 leading-5 text-gray-700 dark:text-gray-200">
                                <span class="block">{{ $item['name'] }}</span>
                                @if($item['description'])
                                    <span class="block text-xs leading-4 text-gray-400">{{ $item['description'] }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-aura::fields.wrapper>
</div>
</x-aura::fields.conditions>
