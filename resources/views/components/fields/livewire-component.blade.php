<style>
  #post-field-{{ optional($field)['slug'] }}-wrapper {
    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
  }

  @media screen and (max-aura::width: 768px) {
    #post-field-{{ optional($field)['slug'] }}-wrapper {
      width: 100%;
    }
  }
</style>

<div class="px-aura::2" id="post-field-{{ optional($field)['slug'] }}-wrapper">
    @livewire($field['component'], ['model' => $this->model])
</div>
