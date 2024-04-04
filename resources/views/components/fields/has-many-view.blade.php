@checkCondition($this->model, $field, $this->form)
    <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">
        <livewire:aura::table 
        wire:key="has-many-{{ microtime() }}-{{$this->model->getType()}}" 
            :model="app($field['resource'])" 
            :field="$field" 
            :editInModal="true"
            :settings="[
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
