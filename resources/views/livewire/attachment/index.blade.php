<div>

    <div wire:key="widgets">
       @if ($widgets = $resource->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
    @endif
    </div>

    <div wire:key="attachment-media-uploader">
        <livewire:aura::media-uploader :table="true" :model="$resource" />
    </div>

    <div wire:key="attachment-details-panel">
        <livewire:aura::attachment-details surface="index" />
    </div>

</div>
