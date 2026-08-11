<div
    data-record-layout-panel="{{ $registeredPanel->identity() }}"
    wire:key="record-layout-panel-{{ hash('sha256', $registeredPanel->identity()) }}"
>
    <livewire:dynamic-component
        :is="$registeredPanel->transport()"
        :model="$model"
        :in-modal="$inModal"
        :wire:key="$registeredPanel->transport().'-'.$model->getKey().'-'.($inModal ? 'modal' : 'page')"
    />
</div>
