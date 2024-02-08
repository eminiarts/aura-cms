<div>
    @section('title', __(Str::of($slug)->title->plural->toString()))

    {{ app('aura')::injectView('index_before') }}

    <div class="flex justify-between items-start">

        {{ app('aura')::injectView('breadcrumbs_before') }}

        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
                iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li :title="__(Str::of($slug)->title->plural->toString())" />
        </x-aura::breadcrumbs>

        {{ app('aura')::injectView('breadcrumbs_after') }}

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

    {{ app('aura')::injectView('widgets_before') }}

    @if ($widgets = $post->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $post])
    @endif

    {{ app('aura')::injectView('widgets_after') }}

    <livewire:aura::table :model="$post" :settings="$post->indexTableSettings()"/>
</div>
