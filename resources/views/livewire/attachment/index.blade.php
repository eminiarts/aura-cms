<div>

    <div class="flex justify-between items-start">

    <div class="mb-6">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
            <x-aura::breadcrumbs.li :title="Str::plural($slug)"  />
        </x-aura::breadcrumbs.li>
    </div>

    <div>
         @if (config('aura.features.resource_editor'))
                    @if ($resource->isAppResource())
                        <x-aura::button.transparent :href="route('aura.resource.editor', $slug)" size="">
                            <x-aura::icon icon="cog" class="mr-2" />
                            {{ __('Edit Resource') }}
                        </x-aura::button.transparent>
                    @endif
            @endif
    </div>

    </div>

    <div wire:key="widgets">
        @if($widgets = $resource->getWidgets())
        <x-aura::widgets :widgets="$widgets" />
        @endif
    </div>

    <div wire:key="attachment-media-uploader">
        <livewire:aura::media-uploader :table="true" :model="$resource" />
    </div>

</div>
