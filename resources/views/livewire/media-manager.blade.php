<div class="" data-media-picker-root x-data="{
    selected: @entangle('selected'),
    _updatingFromEvent: false,
    timeout: null,

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

}" @selection-changed="changeSelected($event)" @media-manager-selected="saveModel()"
    @aura-media-selection-timer-started.window="
        clearTimeout(timeout);
        timeout = setTimeout(
            () => $wire.expireMediaSelection($event.detail.requestToken),
            $event.detail.timeoutMilliseconds
        );
    ">

    <div class="flex items-stretch" x-data="{ detailsOpen: false }"
        x-on:open-attachment-details.window="detailsOpen = true"
        x-on:attachment-details-closed.window="detailsOpen = false">
        <div class="flex-1 min-w-0">
            {{-- @dump('mediamanager', $this->selected, $field) --}}
            <livewire:aura::media-uploader :field="$field" :field-slug="$fieldSlug" :resource="$resource" :for="$model" :selected="$selected" :table="true" :model="app('Aura\Base\Resources\Attachment')" :owner-token="$ownerToken" :details-component-id="$this->getId()" />
        </div>
        <div x-cloak x-show="detailsOpen" class="flex-shrink-0 ml-5 w-80">
            <livewire:aura::attachment-details surface="picker" :owner-token="$ownerToken" :correlation-component-id="$this->getId()" :field-slug="$slug" />
        </div>
    </div>

    <div class="z-[2] relative flex justify-end mt-4">
        @if ($selectionError)
            <p class="mr-auto text-sm text-red-600" role="alert" data-picker-error>
                {{ __('The media selection could not be applied. Please try again.') }}
            </p>
        @endif
        <x-aura::button class="ml-4" data-picker-close x-on:click="if (! $wire.pending) $wire.$dispatch('closeModal')" :disabled="$pending">
            {{ __('Close') }}
        </x-aura::button>
        <x-aura::button.primary class="ml-4" data-picker-select x-on:click="$wire.requestMediaSelection([...selected])" wire:loading.attr="disabled" wire:target="requestMediaSelection" :disabled="$pending">
            {{ $pending ? __('Applying…') : __('Select') }}
        </x-aura::button.primary>
    </div>
</div>
