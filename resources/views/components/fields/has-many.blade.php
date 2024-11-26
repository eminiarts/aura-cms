@checkCondition($this->model, $field)
    <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">
        {{-- <livewire:aura::table :field="$field" :editInModal="true" :namespace="$field['resource']"
             :parent="$model->setRelations([])" /> --}}
    </div>
@endcheckCondition
