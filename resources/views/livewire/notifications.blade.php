<x-aura::slide-over key="notifications" wire:key="notifications" >
    <h1>{{ __('Notifications') }}</h1>

    @foreach($this->fieldsForView as $key => $field)
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
    {{-- <x-aura::fields.conditions :field="$field" :model="$this"> --}}
        <div wire:key="resource-field-{{ $key }}"
        id="resource-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->component" :field="$field" />
        </div>
    {{-- </x-aura::fields.conditions> --}}
    @endforeach

    <x-aura::button wire:click="markAllAsRead">
        <div wire:loading>
            <x-aura::icon.loading  />
        </div>
        {{ __('Mark all as read') }}
    </x-aura::button>

</x-aura::slide-over>
