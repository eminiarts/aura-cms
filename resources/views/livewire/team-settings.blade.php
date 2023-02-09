<div>
    @section('title', 'Edit Settings • ')
    
    <div>
        <h1 class="text-3xl font-semibold">Settings</h1>
        <h3>Set all the team related options </h3>
    </div>
    
    @foreach($this->fieldsForView as $key => $field)
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
    <x-fields.conditions :field="$field" :model="$this">
        <div wire:key="post-field-{{ $key }}"
        id="post-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->component" :field="$field" />
        </div>
    </x-fields.conditions>
    @endforeach
    
    <x-button size="xl" wire:click="save">
        <div wire:loading>
            <x-aura::icon.loading  />
        </div>
        Save
    </x-button>
</div>
