<div>
    @section('title', $resource->pluralName())

    {{ app('aura')::injectView('index_before') }}

    {{-- Breadcrumb row. Phones hide it: the top bar and the page title orient there.
         It names the resource like the page title does (pluralName, not the raw type). --}}
    <div class="hidden sm:block">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
 />
            <x-aura::breadcrumbs.li :title="$resource->pluralName()" />
        </x-aura::breadcrumbs>
    </div>

    {{-- The resource's widgets render inside the table, between the page header and the toolbar
         (components/table/widgets), so the page reads title first. --}}
    {{-- The resource page's table carries the "Edit resource" control in its toolbar (a resource may switch it off). --}}
    <livewire:aura::table :model="$resource" :settings="array_merge(['resource_editor' => true, 'widgets' => true], $resource->indexTableSettings())" />
</div>
