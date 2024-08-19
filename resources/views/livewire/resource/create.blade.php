<div class="">
    @section('title', __('Create ' . $model->singularName()))

    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.resource.index', $slug)" :title="__(Str::plural($slug))" />
        <x-aura::breadcrumbs.li title="{{ __('Create ' . $model->singularName()) }}" />
    </x-aura::breadcrumbs>
    @endif

    <div class="flex items-center justify-between {{ $inModal ? 'mb-8' : 'my-8'}}">
        <div>
            <h1 class="text-3xl font-semibold">{{ __('Create ' . $model->singularName()) }}</h1>
        </div>

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

            <x-aura::validation-errors />

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
</div>
