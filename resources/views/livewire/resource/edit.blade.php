<div autocomplete="off">
    @section('title', __('Edit :resource', ['resource' => __($model->singularName())]))

    @php
        // A simple form keeps a readable width (breadcrumb and header with it, so Save stays at the form's edge) and loses its lone panel heading.
        $formSections = \Aura\Base\RecordLayout\RecordFormSections::arrange($this->editFields);
        // Cancel leaves the form for the record view, or for the list when the record has no view page.
        $cancelUrl = (Route::has('aura.' . $model->getSlug() . '.view') && auth()->user()?->can('view', $model))
            ? $model->viewUrl()
            : (Route::has('aura.' . $model->getSlug() . '.index') ? route('aura.' . $model->getSlug() . '.index') : null);
    @endphp

    <div @class(['max-w-3xl' => $formSections['single'] && ! $inModal])>
    {{ app('aura')::injectView('post_edit_breadcrumbs_before') }}

    @if (!$inModal)
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
 />
            @if (Route::has('aura.' . $model->getSlug() . '.index'))
                <x-aura::breadcrumbs.li :href="route('aura.' . $model->getSlug() . '.index')" :title="$model->pluralName()" />
            @else
                <x-aura::breadcrumbs.li :title="$model->pluralName()" />
            @endif
            {{-- The record links back to its view page, so the form's parent (and the phone back link) is the record.
                 The phone back link names the resource ("‹ Task"): the H1 and the Title input already show the record title. --}}
            @if (Route::has('aura.' . $model->getSlug() . '.view') && auth()->user()?->can('view', $model))
                <x-aura::breadcrumbs.li :href="$model->viewUrl()" :title="$model->title()" :back-title="__($model->singularName())" />
                <x-aura::breadcrumbs.li :title="__('Edit')" />
            @else
                <x-aura::breadcrumbs.li :title="$model->title()" />
            @endif
        </x-aura::breadcrumbs>
    @endif

    {{ app('aura')::injectView('post_edit_breadcrumbs_after') }}

    @include($model->editHeaderView())

    @if ($model::usesTitle())
        <div class="mb-4">
            <x-aura::fields.wrapper :field="['slug' => 'title']" wrapperClass="" class="-mx-4">
                <x-aura::input.text label="Title" wire:model="post.title" error="post.title" placeholder="Title">
                </x-aura::input.text>
            </x-aura::fields.wrapper>
        </div>
    @endif

    @php
        // A narrow panel (style width up to 33%, the sidebar card) beside other fields becomes a right rail
        // from lg, the same 20rem column as on the record view, so edit and view read as one page.
        $isRailPanel = fn ($field) => ($field['field'] ?? null) instanceof \Aura\Base\Fields\Panel
            && is_numeric($field['style']['width'] ?? null) && (float) $field['style']['width'] <= 33;
        $useRail = ! $inModal && ! $formSections['single']
            && collect($formSections['fields'])->contains($isRailPanel)
            && collect($formSections['fields'])->contains(fn ($field) => ! $isRailPanel($field));
    @endphp

    <div class="grid grid-cols-1 gap-6 mt-4 aura-edit-post-container sm:grid-cols-3" x-data="{
        model: @entangle('form'),
        init() {}
    }">
        <div class="col-span-1 mx-0 min-w-0 sm:col-span-3 {{ $useRail ? 'aura-form-rail-layout lg:grid lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-x-10 lg:items-start' : '' }}">
            @if ($useRail)
                <div class="flex flex-wrap items-start -mx-2 min-w-0" autocomplete="off" data-form-column="main">
                    @foreach ($formSections['fields'] as $key => $field)
                        @if (! $isRailPanel($field))
                            @checkCondition($model, $field, $form)
                                <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                                    wire:key="resource-field-{{ $key }}" />
                            @endcheckCondition
                        @endif
                    @endforeach
                </div>

                <aside class="flex flex-wrap items-start -mx-2 min-w-0" data-form-column="rail" data-form-rail>
                    @foreach ($formSections['fields'] as $key => $field)
                        @if ($isRailPanel($field))
                            @checkCondition($model, $field, $form)
                                <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                                    wire:key="resource-field-{{ $key }}" />
                            @endcheckCondition
                        @endif
                    @endforeach
                </aside>
            @elseif ($formSections['card'] && ! $inModal)
                {{-- Plain inputs without a panel get the card a panel would give them (fields/panel). --}}
                <div class="flex flex-wrap items-start -mx-2" autocomplete="off">
                    <div class="px-2 w-full" data-form-card>
                        <div class="mb-8 aura-card">
                            <div class="mb-2">
                                <div class="flex flex-wrap items-start -mx-2 w-[calc(100%+1rem)]">
                                    @foreach ($formSections['fields'] as $key => $field)
                                        @checkCondition($model, $field, $form)
                                            <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                                                wire:key="resource-field-{{ $key }}" />
                                        @endcheckCondition
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex flex-wrap items-start -mx-2" autocomplete="off">
                    @foreach ($formSections['fields'] as $key => $field)
                        @checkCondition($model, $field, $form)
                            <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                                wire:key="resource-field-{{ $key }}" />
                        @endcheckCondition
                    @endforeach
                </div>
            @endif

            <x-aura::validation-errors />
        </div>
    </div>
    </div>

    {{-- Phones: Save and Cancel in a bar on the bottom edge. Only with the default header, whose phone
         layout hands its buttons to the bar; a resource's own header keeps its actions. --}}
    @if (! $inModal && $model->editHeaderView() === 'aura::livewire.resource.edit-header')
        @include('aura::livewire.resource.form-bar', ['cancelUrl' => $cancelUrl])
    @endif

    @if ($inModal)
    <div class="flex justify-end space-x-2 mt-4">
        <x-aura::dialog.close>
            <x-aura::button.transparent>
                {{ __('Cancel') }}
            </x-aura::button.transparent>
        </x-aura::dialog.close>
        <x-aura::button onclick="Livewire.dispatchTo('aura::resource-edit', 'saveModel')" wire:loading.attr="disabled">
            <div wire:loading.delay wire:target="save">
                <x-aura::icon.loading />
            </div>
            {{ __('Save') }}
        </x-aura::button>
    </div>
@endif
</div>
