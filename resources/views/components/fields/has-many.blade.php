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
                'selectable' => false,
            ];

            if(optional($field)['foreign_key']) {
                $createUrl = app($field['resource'])->createUrl();

                if ($createUrl) {
                    $defaultSettings['create_url'] = $createUrl . '?' . http_build_query([$field['foreign_key'] => [$this->model->id]]);
                }
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
