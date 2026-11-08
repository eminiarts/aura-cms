<div>
    @section('title', __($resource->getPluralName()))

    {{ app('aura')::injectView('index_before') }}

    {{-- Breadcrumb row. Phones hide it: the top bar and the page title orient there, and the resource
         editor (a desktop builder) is not something to open on a phone. --}}
    <div class="hidden gap-3 justify-between items-center sm:flex">

        <div class="min-w-0">
            <x-aura::breadcrumbs>
                <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
                    iconClass="text-gray-500 w-6 h-6 mr-0" />
                <x-aura::breadcrumbs.li :title="__($resource->getPluralName())" />
            </x-aura::breadcrumbs>
        </div>

        @if (config('aura.features.resource_editor') && $resource->isAppResource())
            {{-- A developer tool, so a quiet icon button; the name is in the tooltip and for screen readers. --}}
            <x-aura::tippy :text="__('Edit resource')" position="left">
                <x-aura::button.transparent :href="route('aura.resource.editor', $slug)" size="sm" class="shrink-0 w-8 !px-0 [&>span:first-child]:ml-0" aria-label="{{ __('Edit resource') }}">
                    <x-slot:icon>
                        <x-aura::icon icon="cog" />
                    </x-slot>
                    <span class="sr-only">{{ __('Edit resource') }}</span>
                </x-aura::button.transparent>
            </x-aura::tippy>
        @endif
    </div>

    {{ app('aura')::injectView('widgets_before') }}

    @if ($widgets = $resource->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
    @endif

    {{ app('aura')::injectView('widgets_after') }}


    <livewire:aura::table :model="$resource" :settings="$resource->indexTableSettings()" />
</div>
