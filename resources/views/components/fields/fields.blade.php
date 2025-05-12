@aware(['field', 'model', 'form', 'mode'])

<div class="flex flex-wrap items-start -mx-2 w-full">
    @if(optional($field)['fields'])
        @foreach($field['fields'] as $key => $field)
            @checkCondition($this->model ?? $model, $field, $this->form ?? $form)
                <x-dynamic-component :component="$field['field']->{$mode}()" :field="$field" :form="$form" />
            @endcheckCondition
        @endforeach
    @else
        <span>{{ $field['name'] }}</span>
    @endif
</div>
