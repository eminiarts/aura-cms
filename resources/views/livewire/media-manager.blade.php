<div class="p-8 w-full" x-data="{

    selected: @entangle('selected'),

    changeSelected(event) {
        this.selected = event.detail.selected
    },

}" @selection-changed="changeSelected($event)">
    <div class="">
        <livewire:aura::media-uploader :field="$field" :selected="$selected" :table="true" :model="app('Eminiarts\Aura\Resources\Attachment')" />
    </div>

    <div class="flex justify-end mt-4">
        <x-aura::button class="ml-4" wire:click="$emit('closeModal')">
            {{ __('Close') }}
        </x-aura::button>
        <x-aura::button.primary class="ml-4" wire:click="select">
            {{ __('Select') }}
        </x-aura::button.primary>
    </div>
</div>
