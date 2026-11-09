<div wire:key="resource-view-{{ $model->id }}">
    @section('title', __('View :resource', ['resource' => __($model->singularName())]))


    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
        @if(Route::has('aura.' . $model->getSlug() . '.index'))
            <x-aura::breadcrumbs.li :href="route('aura.' . $model->getSlug() . '.index')" :title="$model->pluralName()" />
        @else
            <x-aura::breadcrumbs.li :title="$model->pluralName()" />
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
