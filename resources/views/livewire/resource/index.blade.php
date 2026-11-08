<div>
    @section('title', __($resource->getPluralName()))

    {{ app('aura')::injectView('index_before') }}

    {{-- Breadcrumb row. Phones hide it: the top bar and the page title orient there. --}}
    <div class="hidden sm:block">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
                iconClass="text-gray-500 w-6 h-6 mr-0" />
            <x-aura::breadcrumbs.li :title="__($resource->getPluralName())" />
        </x-aura::breadcrumbs>
    </div>

    {{ app('aura')::injectView('widgets_before') }}

    @if ($widgets = $resource->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
    @endif

    {{ app('aura')::injectView('widgets_after') }}


    {{-- The resource page's table carries the "Edit resource" control in its toolbar (a resource may switch it off). --}}
    <livewire:aura::table :model="$resource" :settings="array_merge(['resource_editor' => true], $resource->indexTableSettings())" />
</div>
