<x-slide-over key="edit-field" wire:key="editPostTypeField">

  <div>
    @if($field)
         <!-- Buttons -->

    <div class="flex">
      <div class="flex-1">
        <h2 class="text-3xl font-bold">Edit Field</h2>
        <h1 class="mb-4 text-3xl font-semibold">{{ $field['label'] ?? ''}} ({{ $field['slug'] }})</h1>
      </div>
      <div class="mt-10 space-x-2">
        <x-button.danger wire:click="deleteField('{{ $field['slug'] }}')">
            <x-slot:icon>
                <x-icon.edit class="w-5 h-5" />
            </x-slot>
            Delete
        </x-button.danger>
      </div>
    </div>


    @dump($this->post)

    {{-- @foreach($this->fields as $key => $field)
    <div wire:key="post-field-{{ $key }}"
    style="width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;">
    <x-dynamic-component :component="$field['field']->component" :field="$field" />
    </div>
    @endforeach --}}

    {{-- @dump($field['field']->mappedFields()) --}}
    {{-- @dump($model->mappedFields()[$fieldSlug - 1]['field']->mappedFields()) --}}

    {{-- @dd($model->mappedFieldBySlug($fieldSlug)['field']->getGroupedFields()) --}}

    @foreach(app($field['type'])->getGroupedFields() as $key => $field)
      <style>
        #post-field-{{ optional($field)['slug'] }}-wrapper {
          width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
        }

        @media screen and (max-width: 768px) {
          #post-field-{{ optional($field)['slug'] }}-wrapper {
            width: 100%;
          }
        }
      </style>
      <div wire:key="posttype-field-{{ $key }}"
      id="post-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->component" :field="$field" />
      </div>
    @endforeach






    @endif

  </div>



  <div class="flex mt-8 space-x-2">
        <x-button wire:click="save">
            <x-slot:icon>
                <x-icon.edit class="w-5 h-5" />
            </x-slot>
            Save
        </x-button>

        <x-button.border x-on:click="open = false">
            Cancel
        </x-button.border>
  </div>

</x-slide-over>
