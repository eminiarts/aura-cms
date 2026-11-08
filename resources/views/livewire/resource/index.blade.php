<div>
    @section('title', __($resource->getPluralName()))

    {{ app('aura')::injectView('index_before') }}

    <div class="flex gap-3 justify-between items-center">

        <div class="min-w-0">
            <x-aura::breadcrumbs>
                <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
                    iconClass="text-gray-500 w-6 h-6 mr-0" />
                <x-aura::breadcrumbs.li :title="__($resource->getPluralName())" />
            </x-aura::breadcrumbs>
        </div>

        @if (config('aura.features.resource_editor') && $resource->isAppResource())
            {{-- Icon-only on phones so it fits the breadcrumb row; the label stays for screen readers. --}}
            <x-aura::button.transparent :href="route('aura.resource.editor', $slug)" size="sm" class="shrink-0 max-sm:w-8 max-sm:px-0 max-sm:[&>span:first-child]:ml-0" title="{{ __('Edit Resource') }}">
                <x-slot:icon>
                    <x-aura::icon icon="cog" />
                </x-slot>
                <span class="sr-only sm:not-sr-only">{{ __('Edit Resource') }}</span>
            </x-aura::button.transparent>
        @endif
    </div>

    {{ app('aura')::injectView('widgets_before') }}

    @if ($widgets = $resource->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
    @endif

    {{ app('aura')::injectView('widgets_after') }}


    <livewire:aura::table :model="$resource" :settings="$resource->indexTableSettings()" />
</div>
