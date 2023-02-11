@php
$slug = $field['slug'];
if (!isset($this->post['fields'][$slug])) {
  $this->post['fields'][$slug] = [];
}

$model = $this->model;
@endphp

<style>
  #post-field-{{ Str::slug(optional($field)['slug']) }}-wrapper {
    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
  }

  @media screen and (max-width: 768px) {
    #post-field-{{ Str::slug(optional($field)['slug']) }}-wrapper {
      width: 100%;
    }
  }
</style>


<div class="px-2 mt-4" id="post-field-{{ Str::slug(optional($field)['slug']) }}-wrapper">
  <div class="p-0">
    <h2 class="px-2 font-semibold">{{ $field['name'] }}</h2>
    <div class="flex flex-wrap items-start -mx-2">
      @if(optional($field)['fields'])
      @foreach($field['fields'] as $key => $field)
      <x-aura::fields.conditions :field="$field" :model="$this->post">
        <x-dynamic-component :component="$field['field']->component" :field="$field" />
        </x-aura::fields.conditions>
        @endforeach
        @else
        <span>{{ $field['name'] }}</span>
        @endif
      </div>
    </div>
  </div>
