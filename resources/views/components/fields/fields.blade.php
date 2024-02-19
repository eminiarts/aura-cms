@aware(['field', 'model', 'form'])
<div class="flex flex-wrap items-start -mx-2" wire:key="tab-field-{{ md5(json_encode($field)) }}">
    @if(optional($field)['fields'])
        @foreach($field['fields'] as $key => $field)
            @checkCondition($this->model ?? $model, $field, $this->form ?? $form)
                <x-dynamic-component :component="$field['field']->component()" :field="$field" :form="$form" />
            @endcheckCondition
        @endforeach
    @else
        <span>{{ $field['name'] }}</span>
    @endif
</div>
