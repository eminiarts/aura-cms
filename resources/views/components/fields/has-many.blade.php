@checkCondition($this->model, $field, $this->post)
    <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">

        {{-- @dump($field) --}}
            unset($field['conditional_logic']);
        @endphp

        <livewire:aura::table 
        
        :model="app($field['resource'])" 
        :field="$field" 
        :editInModal="true" 
        :createInModal="true" 
        :parent="$this->model->setRelations([])"
        :settings="$field['table'] ?? []"
         />
    </div>
@endcheckCondition
   