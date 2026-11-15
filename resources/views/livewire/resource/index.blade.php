<div>
    @section('title', $resource->pluralName())

    {{ app('aura')::injectView('index_before') }}

    @php
        // A list page is a top-level page: no trail (it would only repeat the title), the bookmark sits beside
        // the title (components/table/header). A list under another page (Options under Settings) keeps the
        // trail "Settings / Options". Phones hide the trail: the top bar and the page title orient there.
        $breadcrumbParent = $resource->indexBreadcrumbParent();
    @endphp

    @if ($breadcrumbParent)
        <div class="hidden sm:block">
            <x-aura::breadcrumbs>
                <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
                <x-aura::breadcrumbs.li :href="$breadcrumbParent['href']" :title="$breadcrumbParent['title']" />
                <x-aura::breadcrumbs.li :title="$resource->pluralName()" />
            </x-aura::breadcrumbs>
        </div>
    @endif

    {{-- The resource's widgets render inside the table, between the page header and the toolbar
         (components/table/widgets), so the page reads title first. --}}
    {{-- The resource page's table carries the "Edit resource" control in its toolbar (a resource may switch it off). --}}
    <livewire:aura::table :model="$resource" :settings="array_merge(['resource_editor' => true, 'widgets' => true, 'page_bookmark' => ! $breadcrumbParent], $resource->indexTableSettings())" />
</div>
