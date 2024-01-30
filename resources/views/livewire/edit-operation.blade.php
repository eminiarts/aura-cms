@php
  use Aura\Flows\Resources\Operation;
@endphp
<x-aura::slide-over key="edit-operation" wire:key="editOperation" >
  <div x-data="{
    post: @entangle('post').live,
      init () {
        // alpine watch post
        this.$watch('post', (value) => {
          const select = document.getElementById('post_fields_type');
          select.addEventListener('change', (event) => {
            @this.updateType();
          });

        });
      }
    }">
    @if($model)
    <div class="flex">
      <div class="flex-1">
        <h2 class="text-3xl font-bold">Edit Operation</h2>
        <h1 class="mb-4 text-3xl font-semibold">{{ $model['name'] }}</h1>
      </div>
      <div class="mt-10 space-x-2">
        <x-aura::button.danger wire:click="deleteOperation('{{ $model['id'] }}')">
            <x-slot:icon>
                <x-aura::icon.edit class="w-5 h-5" />
            </x-slot>
            Delete
        </x-aura::button.danger>
      </div>
    </div>

    {{-- @dump($post) --}}
    {{-- @dump($this->groupedFields) --}}

      @foreach($this->model->getGroupedFields() as $key => $field)
        <style >
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
        <x-aura::button wire:click="save">
            <x-slot:icon>
                <x-aura::icon.edit class="w-5 h-5" />
            </x-slot>
            {{ __('Save') }}
        </x-aura::button>

        <x-aura::button.border wire:click="validateBeforeClosing()">
            {{ __('Cancel') }}
        </x-aura::button.border>
  </div>
</x-aura::slide-over>
