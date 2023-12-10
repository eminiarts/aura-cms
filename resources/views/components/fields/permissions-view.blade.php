@aware(['model'])
@php
    $name = 'post.fields.'  . optional($field)['slug'];


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
<div class="w-full">
<x-aura::fields.wrapper :field="$field">

    @foreach($groups as $group => $items)
        <div class="flex flex-row justify-between py-6">
            <div class="pr-1.5 w-1/3">
                <h4 class="font-bold">{{ $group  }}</h4>
            </div>
            <div class="pl-1.5 w-2/3">
                @foreach($items as $item)
                    <div class="flex items-center mb-2">
                        @if(optional($this->post['fields']['permissions'])[$item['slug']])
                            <span class="text-green-500">&#10004;</span> <!-- This is a check mark -->
                        @else
                            <span class="text-red-500">&#10006;</span> <!-- This is an x mark -->
                        @endif
                        <label class="block ml-3 text-sm leading-5 text-gray-700 cursor-pointer dark:text-gray-200">
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
