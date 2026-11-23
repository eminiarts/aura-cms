<x-aura::slide-over key="notifications" wire:key="notifications" >
    <h1>{{ __('Notifications') }}</h1>

    {{-- Field cells pad themselves by half the layout gap; the row pulls them back to the content edge. --}}
    <div class="aura-layout-row">
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
        <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" mode="edit" />
        </div>
    {{-- </x-aura::fields.conditions> --}}
    @endforeach
    </div>

    <x-aura::button wire:click="markAllAsRead">
        <div wire:loading>
            <x-aura::icon.loading  />
        </div>
        {{ __('Mark all as read') }}
    </x-aura::button>

</x-aura::slide-over>
