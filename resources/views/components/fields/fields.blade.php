@aware(['field', 'model'])

<div class="flex flex-wrap items-start -mx-2">
    @if(optional($field)['fields'])
        @foreach($field['fields'] as $key => $field)
            <x-fields.conditions :field="$field" :model="$model">
                <x-dynamic-component :component="$field['field']->component" :field="$field" />
            </x-fields.conditions>
        @endforeach
    @else
        <span>{{ $field['name'] }}</span>
    @endif
</div>
