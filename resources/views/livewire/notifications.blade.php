<x-slide-over key="notifications" wire:key="notifications" >
    <h1>Notifications</h1>

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
    
    <x-button wire:click="markAllAsRead">
        <div wire:loading>
            <x-aura::icon.loading  />
        </div>
        Mark all as read
    </x-button>

</x-slide-over>
