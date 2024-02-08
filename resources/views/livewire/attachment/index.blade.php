<div>

    <div class="flex items-start justify-between">

        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li :title="Str::plural($slug)"  />
        </x-aura::breadcrumbs.li>

    <div>
         @if (config('aura.features.resource_editor'))
                    @if ($post->isAppResource())
                        <x-aura::button.transparent :href="route('aura.resource.editor', $slug)" size="">
                            <x-aura::icon icon="cog" class="mr-2" />
                            {{ __('Edit Resource') }}
                        </x-aura::button.transparent>
                    @endif
            @endif
    </div>

    </div>

    <div wire:key="widgets">
        @if($widgets = $post->getWidgets())
        <x-aura::widgets :widgets="$widgets" />
        @endif
    </div>

    <div wire:key="attachment-media-uploader">
        <livewire:aura::media-uploader :table="true" :model="$post" />
    </div>

</div>
