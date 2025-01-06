@checkCondition($this->model, $field, $this->form)
    <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">
        @php
            unset($field['relation']);
            unset($field['conditional_logic']);
            $defaultSettings = [
                'filters' => false,
                'global_filters' => false,
                'header_before' => false,
                'header_after' => false,
                'settings' => false,
                'search' => false,
            ];

            if(optional($field)['foreign_key']) {
                $defaultSettings['create_url']  = app($field['resource'])->createUrl() . '?' . $field['foreign_key']  . '=' . $this->model->id;
                
                $defaultSettings['create_url']  = app($field['resource'])->createUrl() . '?' . http_build_query([$field['foreign_key'] => [$this->model->id]]);
            }

            $mergedSettings = array_merge($defaultSettings, $field['table_settings'] ?? []);
        @endphp

        <livewire:aura::table
            :model="app($field['resource'])"
            :field="$field"
            :settings="$mergedSettings"
            :parent="$this->model"
            :disabled="true"
            />
    </div>
@endcheckCondition
