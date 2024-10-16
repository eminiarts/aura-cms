<div>
    @section('title', __(Str::of($slug)->title->plural->toString()))

    {{ app('aura')::injectView('index_before') }}

    <div class="flex justify-between items-start">

        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
                iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li :title="__(Str::of($slug)->title->plural->toString())" />
        </x-aura::breadcrumbs>

        <div>
            @if (config('aura.resource_editor.enabled'))
                    @if ($resource->isAppResource())
                        <x-aura::button.transparent :href="route('aura.resource.editor', $slug)" size="">
                            <x-aura::icon icon="cog" class="mr-2" />
                            {{ __('Edit Resource') }}
                        </x-aura::button.transparent>
                    @endif
            @endif
        </div>
    </div>

    {{ app('aura')::injectView('widgets_before') }}
    @if ($widgets = $resource->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
    @endif

    {{ app('aura')::injectView('widgets_after') }}

    <livewire:aura::table :model="$resource" :settings="$resource->indexTableSettings()" />
</div>
