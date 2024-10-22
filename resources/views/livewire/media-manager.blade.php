<div class="" x-data="{

    selected: @entangle('selected').live,

    saveModel() {
        // Save Model when Media Manager is closed
        $wire.$dispatch('saveModel')
    },

    changeSelected(event) {
        {{-- if (this.selected == event.detail.selected) {
            return;
        } --}}

        this.selected = event.detail.selected

        {{-- console.log($wire); --}}
         {{-- $wire.$dispatch('saveModel') --}}
    },

}" @selection-changed="changeSelected($event)" @media-manager-selected="saveModel()">

    <div class="">
        {{-- @dump('mediamanager', $this->selected, $field) --}}
        <livewire:aura::media-uploader :field="$field" :selected="$selected" :table="true" :model="app('Aura\Base\Resources\Attachment')" />
    </div>

    @dump($modalAttributes)

    <div class="z-[2] relative flex justify-end mt-4">
        <x-aura::button class="ml-4">
            {{ __('Close') }}
        </x-aura::button>
        <x-aura::button.primary class="ml-4" wire:click="select">
            {{ __('Select') }}
        </x-aura::button.primary>
    </div>
</div>
