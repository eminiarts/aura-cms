<div>
    @section('title', __(Str::of($slug)->title->plural->toString()))

    <div class="flex items-start justify-between">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li :title="__(Str::of($slug)->title->plural->toString())"  />
        </x-aura::breadcrumbs.li>

        <div>
            @local
            @if($post->isAppResource())
            <x-aura::button.transparent :href="route('aura.posttype.edit', $slug)" size="" >
                <x-aura::icon icon="cog" class="mr-2" />
                {{ __('Edit Posttype') }}
            </x-aura::button.transparent>
            @endif
            @endlocal
        </div>
    </div>

    @if($widgets = $post->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $post] )
    @endif

    <livewire:aura::table :model="$post"/>
</div>
