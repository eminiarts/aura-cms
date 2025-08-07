<div class="">
    @section('title', __('Create ' . $model->singularName()))


    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.' . $model->getSlug() . '.index')" :title="__($model->getPluralName())" />
        <x-aura::breadcrumbs.li title="{{ __('Create :resource', ['resource' => __($model->singularName())]) }}" />
    </x-aura::breadcrumbs>
    @endif

    <div class="flex items-center justify-between {{ $inModal ? 'mb-8' : 'my-8'}}">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('Create :resource', ['resource' => __($model->singularName())]) }}</h1>
        </div>

        @if(!$inModal)
        <div class="save-resource">
            @if($showSaveButton)
            <x-aura::button size="lg" wire:click="save">
                <div wire:loading wire:target="save">
                    <x-aura::icon.loading />
                </div>
                {{ __('Save') }}
            </x-aura::button>
            @endif
        </div>
        @endif
    </div>

    @if($model::usesTitle())
    <div class="mb-4">
        <x-aura::fields.wrapper :field="['slug' => 'title']" wrapperClass="" class="-mx-4">
            <x-aura::input.text wire:model="post.title" error="post.title" placeholder="Title"></x-aura::input.text>
        </x-aura::fields.wrapper>
    </div>
    @endif

    <div class="grid gap-6 aura-edit-post-container sm:grid-cols-3">
        <div class="col-span-1 sm:col-span-3">

            @if(!$inModal)
            <x-aura::validation-errors />
            @endif

            <div class="flex flex-wrap items-start -mx-2">
           @foreach($this->createFields as $key => $field)
            @checkCondition($model, $field, $form)
                    <x-dynamic-component :component="$field['field']->edit()" :field="$field" wire:key="resource-field-{{ $key }}" :form="$form" :mode="$mode" />
            @endcheckCondition
            @endforeach
            </div>

            <x-aura::validation-errors />
        </div>

        {{-- <div class="col-span-1">
            <div class="aura-card">
                <x-aura::button size="xl" wire:click="save">Save</x-aura::button>
            </div>
        </div> --}}
    </div>

    @if ($inModal)
        <div class="flex justify-end space-x-2 mt-4">
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
        </div>
    @endif
</div>
