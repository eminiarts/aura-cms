<div>



    <div wire:key="widgets">
        @if($widgets = $resource->getWidgets())
        <x-aura::widgets :widgets="$widgets" />
        @endif
    </div>

    <div wire:key="attachment-media-uploader">
        <livewire:aura::media-uploader :table="true" :model="$resource" />
    </div>

</div>
