
<div class="mt-8 w-full px-aura::2 {{ $field['style']['class'] ?? '' }}">
  <livewire:table.table
  :model="app($field['posttype'])"
  :field="$field['type']"
  :editInModal="true"
  :createInModal="true"
  :parent="$this->model"
  />
</div>
