<div class="" x-data="{
    selected: @entangle('selected'),
    _updatingFromEvent: false,

    saveModel() {
        // Save Model when Media Manager is closed
        $wire.$dispatch('saveModel')
    },

    changeSelected(event) {
        // Only update if values are actually different to prevent circular updates
        const newSelected = event.detail.selected || [];
        const currentSelected = this.selected || [];

        // Compare arrays - if they're the same, don't update
        if (JSON.stringify([...newSelected].sort()) === JSON.stringify([...currentSelected].sort())) {
            return;
        }

        this._updatingFromEvent = true;
        this.selected = [...newSelected];
        this.$nextTick(() => {
            this._updatingFromEvent = false;
        });
    },

}" @selection-changed="changeSelected($event)" @media-manager-selected="saveModel()">

    <div class="">
        {{-- @dump('mediamanager', $this->selected, $field) --}}
        <livewire:aura::media-uploader :field="$field" :selected="$selected" :table="true" :model="app('Aura\Base\Resources\Attachment')" />
    </div>

    <div class="z-[2] relative flex justify-end mt-4">
        <x-aura::button class="ml-4" x-on:click="$dialog.close()">
            {{ __('Close') }}
        </x-aura::button>
        <x-aura::button.primary class="ml-4" x-on:click="$wire.select([...selected]).then(() => { setTimeout(() => $dialog.close(), 100) })">
            {{ __('Select') }}
        </x-aura::button.primary>
    </div>
</div>
