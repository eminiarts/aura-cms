@checkCondition($this->model, $field, $this->form)
    <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">

        @php
            unset($field['relation']);
            unset($field['conditional_logic']);
        @endphp

        <livewire:aura::table 
            :model="app($field['resource'])" 
            :field="$field" 
            :editInModal="false"
            :settings="$field['table_settings'] ?? [
                'filters' => false,
                'actions' => false,
                'global_filters' => false,
                'create' => false,
                'header_before' => false,
                'header_after' => false,
                'settings' => false,
                'search' => false,
                // 'columns' => [
                //     'name' => 'Name',
                //     'contact_email' => 'Contact Email'
                // ]
            ]"
            :createInModal="true" 
            :parent="$this->model->setRelations([])" 
            :disabled="true" 
            />
    </div>
@endcheckCondition
