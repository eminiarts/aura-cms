@checkCondition($this->model, $field, $this->post)
    <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">

        {{-- @dump($field) --}}

        <livewire:aura::table 
        wire:key="has-many-{{ $field['slug'] ?? '' }}" 
        :model="app($field['resource'])" 
        :field="$field" 
        :editInModal="true" 
        :createInModal="true" 
        :parent="$this->model->setRelations([])"
        :settings="$field['table'] ?? []"
         />
    </div>
@endcheckCondition
   