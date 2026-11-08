@php
    // Header rule shared with view and edit: the eyebrow names the resource only above a record title.
    // A new record has no title yet, so the H1 says "New deal" (sentence case, acronyms kept) and there is no eyebrow.
    $newRecordTitle = __('New :resource', [
        'resource' => collect(preg_split('/\s+/u', trim(__($model->singularName()))))
            ->map(fn ($word) => mb_strlen($word) > 1 && mb_strtoupper($word) === $word ? $word : mb_strtolower($word))
            ->implode(' '),
    ]);
    $indexUrl = Route::has('aura.' . $model->getSlug() . '.index') ? route('aura.' . $model->getSlug() . '.index') : null;
@endphp
<div class="">
    @section('title', $newRecordTitle)


    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
        <x-aura::breadcrumbs.li :href="$indexUrl" :title="__($model->getPluralName())" />
        <x-aura::breadcrumbs.li :title="$newRecordTitle" />
    </x-aura::breadcrumbs>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between {{ $inModal ? 'mb-8' : 'mt-1 mb-6 sm:my-8'}}">
        <div class="min-w-0">
            <h1 class="break-words aura-page-title">{{ $newRecordTitle }}</h1>
        </div>

        @if(!$inModal)
        <div class="flex gap-2 items-center shrink-0 save-resource">
            @if($indexUrl)
            <x-aura::button.transparent :href="$indexUrl">
                {{ __('Cancel') }}
            </x-aura::button.transparent>
            @endif
            @if($showSaveButton)
            <x-aura::button wire:click="save">
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

    <div class="grid grid-cols-1 gap-6 aura-edit-post-container sm:grid-cols-3">
        <div class="col-span-1 min-w-0 sm:col-span-3">

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
