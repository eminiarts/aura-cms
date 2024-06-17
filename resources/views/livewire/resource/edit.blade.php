<div autocomplete="off">
    @section('title', __('Edit ' . $model->singularName()))

    {{ app('aura')::injectView('post_edit_breadcrumbs_before') }}

    @if (!$inModal)
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
                iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li :href="route('aura.resource.index', $slug)" :title="__(Str::plural($slug))" />
            <x-aura::breadcrumbs.li :title="$model->title()" />
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

    <div class="grid gap-6 mt-4 aura-edit-post-container sm:grid-cols-3" x-data="{
        model: @entangle('form'),
        init() {}
    }">
        <div class="col-span-1 mx-0 sm:col-span-3">
            <div class="flex flex-wrap items-start -mx-2" autocomplete="off">
                @foreach ($this->editFields as $key => $field)
                    @checkCondition($model, $field, $form)
                        <x-dynamic-component :component="$field['field']->component" :field="$field" :form="$form"
                            wire:key="resource-field-{{ $key }}" />
                    @endcheckCondition
                @endforeach
            </div>

            <x-aura::validation-errors />
        </div>
    </div>

    @if ($inModal)
    <x-slot:footer>
         <x-aura::dialog.close>
                    <x-aura::button.transparent>
                        {{ __('Cancel') }}
                    </x-aura::button.transparent>
                </x-aura::dialog.close>
            <x-aura::button wire:click="save" wire:loading.attr="disabled">
                <div wire:loading.delay wire:target="save">
                    <x-aura::icon.loading />
                </div>
                {{ __('Save') }}
            </x-aura::button>
    </x-slot>
     
    @endif
</div>
