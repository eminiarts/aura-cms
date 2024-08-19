<x-aura::slide-over key="edit-field" wire:key="editPostTypeField">

  <div x-data="{
    form: @entangle('form').live,
      init () {
        // alpine watch post
        this.$watch('form', (value) => {
          const select = document.getElementById('post_fields_type');
          select.addEventListener('change', (event) => {
            @this.updateType();
          });

        });
      }
    }">

    @if($field)
      <div class="flex">
        <div class="flex-1 truncate">
          <h2 class="text-3xl font-bold">
            @if($mode == 'create')
              {{ __('Add Field') }}
            @else
            {{ __('Edit Field') }}
            @endif
          </h2>
          @if($field['slug'])
          <h3 class="mb-4 text-xl font-semibold truncate">{{ $field['label'] ?? ''}} ({{ $field['slug'] }})</h3>
          @endif
        </div>
        <div class="flex-shrink-0 mt-10 space-x-2">
          @if($mode == 'edit')
          <x-aura::button.danger wire:click="deleteField('{{ $field['slug'] }}')">
              <x-slot:icon>
                  <x-aura::icon.trash class="w-5 h-5" />
              </x-slot>
              {{ __('Delete') }}
          </x-aura::button.danger>
          @endif
        </div>
      </div>

      @foreach($this->groupedFields as $key => $field)
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
        <div wire:key="resource-field-{{ $key }}"
        id="resource-field-{{ optional($field)['slug'] }}-wrapper">
          <x-dynamic-component :component="$field['field']->edit()" mode="edit" :field="$field" :form="$form" />
        </div>
      @endforeach
    @endif

  </div>

  <div class="flex mt-8 space-x-2">
        <x-aura::button wire:click="save">
            {{ __('Save') }}
        </x-aura::button>

        <x-aura::button.border x-on:click="open = false">
            {{ __('Cancel') }}
        </x-aura::button.border>
  </div>

</x-aura::slide-over>
