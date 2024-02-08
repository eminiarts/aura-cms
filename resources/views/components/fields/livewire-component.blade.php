<style >
  #resource-field-{{ optional($field)['slug'] }}-wrapper {
    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
  }

  @media screen and (max-width: 768px) {
    #resource-field-{{ optional($field)['slug'] }}-wrapper {
      width: 100%;
    }
  }
</style>

<div class="px-2" id="post-field-{{ optional($field)['slug'] }}-wrapper">
    @livewire($field['component'], ['model' => $this->model, 'field' => $field])
</div>
