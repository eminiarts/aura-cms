<div wire:key="resource-view-{{ $model->id }}">
    @section('title', __('View :resource', ['resource' => __($model->singularName())]))


    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
        @if(Route::has('aura.' . $model->getSlug() . '.index'))
            <x-aura::breadcrumbs.li :href="route('aura.' . $model->getSlug() . '.index')" :title="__($model->getPluralName())" />
        @else
            <x-aura::breadcrumbs.li :title="__($model->getPluralName())" />
        @endif
        <x-aura::breadcrumbs.li :title="$model->title()" />
    </x-aura::breadcrumbs>
    @endif

    @include($model->viewHeaderView())

    @if ($recordLayout->hasPanels())
        @include('aura::livewire.resource.record-layout.layout')
    @else
        @include('aura::livewire.resource.record-layout.default-main')
    @endif

</div>
