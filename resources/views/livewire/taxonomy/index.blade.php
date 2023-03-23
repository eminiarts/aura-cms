<div>
    @section('title', 'All '. Str::plural($slug) . ' • ')

    <div class="flex items-start justify-between">

        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li :title="Str::plural($slug)"  />
        </x-aura::breadcrumbs.li>

    </div>

    @if($widgets = $taxonomy->getWidgets())
    <x-aura::widgets :widgets="$widgets" />
    @endif

    <livewire:aura::table :model="$taxonomy" :slug="$slug"/>
</div>
