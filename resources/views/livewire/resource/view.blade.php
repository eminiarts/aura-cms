<div wire:key="resource-view-{{ $model->id }}">
    @section('title', __('View :resource', ['resource' => __($model->singularName())]))

    @php
        // A record with nothing beside its values (no related lists, relation cards or rail) takes its form's
        // frame, so view and edit read as one page: a simple record keeps the form's readable width (breadcrumb
        // and header with it, so Edit sits where Save sits), tabs and panels look as on the form.
        $formFrame = $recordLayout->hasPanels() ? null : \Aura\Base\RecordLayout\RecordViewSections::formFrame($this->viewFields, $model);
    @endphp

    <div @class(['max-w-3xl' => ($formFrame['single'] ?? false) && ! $inModal])>
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

</div>
