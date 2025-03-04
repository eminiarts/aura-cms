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
    removeAll(group){
        this.groups[group].forEach(item => {
            this.field[item.slug] = false;
        });
    },
}">
<x-aura::fields.wrapper :field="$field">

    {{-- button to select all --}}
     <div class="flex justify-end mb-2">
        <x-aura::button.transparent @click="selectAllGroups()">
            {{ __('Select all') }}
        </x-aura::button.transparent>

    </div>

    @foreach($groups as $group => $items)
        <div class="flex flex-row justify-between py-6">
            <div class="pr-1.5 w-1/3">
                <h4 class="font-bold">{{ $group  }}</h4>
                <div class="flex space-x-2">
                    <div class="text-sm text-gray-500 cursor-pointer" @click="selectAll('{{ $group }}')">{{ __('Select all') }}</div>
                    <span>|</span>
                    <div class="text-sm text-gray-500 cursor-pointer" @click="removeAll('{{ $group }}')">{{ __('Remove all') }}</div>
                </div>
            </div>
            <div class="pl-1.5 w-2/3">
                @foreach($items as $item)
                    <div class="flex items-center mb-2">
                        <input x-model="field['{{ $item['slug'] }}']" type="checkbox" id="permissions_{{ $item['slug'] }}" class="w-5 h-5 bg-gray-100 rounded transition duration-150 ease-in-out cursor-pointer border-gray-500/30 form-checkbox text-primary-600 focus:ring-primary-500">
                        <label for="permissions_{{ $item['slug'] }}" class="block ml-3 text-sm leading-5 text-gray-700 cursor-pointer dark:text-gray-200">
                            {{ $item['name'] }}
                            <span class="block text-xs text-gray-400">{{ $item['description'] }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <hr>
    @endforeach
</x-aura::fields.wrapper>
</div>
</x-aura::fields.conditions>
