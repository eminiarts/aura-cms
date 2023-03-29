@checkCondition($this->model, $field)
     <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">
         <livewire:aura::table :model="app($field['resource'])" :field="$field" :editInModal="true"
             :createInModal="true" :parent="$this->model" />
     </div>
@endcheckCondition