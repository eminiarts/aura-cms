
<div class="mt-8 w-full px-2 {{ $field['style']['class'] ?? '' }}">
  <livewire:aura::table
  :model="app($field['resource'])"
  :field="$field['type']"
  :editInModal="true"
  :createInModal="true"
  :parent="$this->model"
  />
</div>