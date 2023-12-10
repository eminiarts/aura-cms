<div class="p-8 w-full" x-data="{ 
    
    init() {
        {{-- @this.on('selection-changed', payload => {
            console.log('chatch me outside')

        }); --}}
    },
    changeSelected(event) {
        console.log('changeSelected', event.detail)
        {{-- @this.set('selected', event.detail) --}}
    },

}" @selectionChanged.window="console.log('hererererererrerererererer')">
    <div class="">
        <livewire:aura::media-uploader :field="$field" :selected="$selected" :table="true" :model="app('Eminiarts\Aura\Resources\Attachment')" />
    </div>

    {{-- Footer with 2 buttons: close and select --}}
    <div class="flex justify-end mt-4">
        <x-aura::button class="ml-4" wire:click="$emit('closeModal')">
            {{ __('Close') }}
        </x-aura::button>
        <x-aura::button.primary class="ml-4" wire:click="select">
            {{ __('Select') }}
        </x-aura::button.primary>
</div>
