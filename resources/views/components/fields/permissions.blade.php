@aware(['model'])
@php
    $name = 'post.fields.'  . optional($field)['slug'];

    $groups = app($field['posttype'])->get()->map(function($item, $key) {
        return [
            'name' => $item->fields['name'],
            'description' => $item->fields['description'],
            'slug' => $item->fields['slug'],
            'group' => $item->group,
        ];
    })->groupBy('group')->toArray();

@endphp

hallo

<x-fields.conditions :field="$field" :model="$model">
<div class="w-full" x-data="{
    groups: @js($groups),
    field: @entangle($name).defer,
    init(){
        console.log('init permissions', this.groups);
        // if this.field is null, set it to an empty array
        if (this.field === null) {
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
            this.field[item.slug] = true;
        });
    },
    removeAll(group){
        this.groups[group].forEach(item => {
            this.field[item.slug] = false;
        });
    },
}">
<x-fields.wrapper :field="$field">
    @foreach($groups as $group => $items)
        <div class="flex flex-row justify-between py-6">
            <div class="w-1/3 pr-1.5">
                <h4 class="font-bold">{{ $group  }}</h4>
                <div class="flex space-x-2">
                    <div class="text-sm text-gray-500 cursor-pointer" @click="selectAll('{{ $group }}')">Select all</div>
                    <span>|</span>
                    <div class="text-sm text-gray-500 cursor-pointer" @click="removeAll('{{ $group }}')">Remove all</div>
                </div>
            </div>
            <div class="w-2/3 pl-1.5">
                @foreach($items as $item)
                    <div class="flex items-center mb-2 ">
                        <input x-model="field['{{ $item['slug'] }}']" type="checkbox" id="permissions_{{ $item['slug'] }}" class="w-5 h-5 transition duration-150 ease-in-out bg-gray-100 border-gray-500/30 rounded cursor-pointer form-checkbox text-primary-600 focus:ring-primary-500">
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
</x-fields.wrapper>
</div>
</x-fields.conditions>
