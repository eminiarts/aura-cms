<div>
    @section('title', __($resource->getPluralName()))

    {{ app('aura')::injectView('index_before') }}

    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
            iconClass="text-gray-500 w-6 h-6 mr-0" />
        <x-aura::breadcrumbs.li :title="__($resource->getPluralName())" />
    </x-aura::breadcrumbs>

    {{ app('aura')::injectView('widgets_before') }}

    @if ($widgets = $resource->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
    @endif

    {{ app('aura')::injectView('widgets_after') }}


    {{-- "Edit resource" lives in the table toolbar's "more" menu (components/table/resource-editor-button). --}}
    <livewire:aura::table :model="$resource" :settings="array_merge(['resource_editor' => true], $resource->indexTableSettings())" />
</div>
