@php
    // Header rule shared with view and edit: the eyebrow names the resource only above a record title.
    // A new record has no title yet, so the H1 says "New deal" (sentence case, acronyms kept) and there is no eyebrow.
    $newRecordTitle = __('New :resource', ['resource' => \Aura\Base\RecordLayout\RecordFormSections::inSentence(__($model->singularName()))]);
    $indexUrl = Route::has('aura.' . $model->getSlug() . '.index') ? route('aura.' . $model->getSlug() . '.index') : null;
    // A simple form keeps a readable width (breadcrumb and header with it, so Save stays at the form's edge) and loses its lone panel heading.
    $formSections = \Aura\Base\RecordLayout\RecordFormSections::arrange($this->createFields);
@endphp
<div class="">
    @section('title', $newRecordTitle)


    <div @class(['max-w-3xl' => $formSections['single'] && ! $inModal])>
    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
        <x-aura::breadcrumbs.li :href="$indexUrl" :title="$model->pluralName()" />
        <x-aura::breadcrumbs.li :title="$newRecordTitle" />
    </x-aura::breadcrumbs>
    @endif

    {{-- Title left, actions right, as on the record header; on phones Cancel and Save sit in the bottom form bar instead. --}}
    <div class="aura-record-block {{ $inModal ? 'mb-8' : 'mt-1 mb-6 sm:my-8'}}">
    <div class="aura-record-header">
        <div class="flex-1 min-w-0 sm:min-w-[10rem]">
            <h1 class="break-words aura-page-title line-clamp-2 sm:line-clamp-1">{{ $newRecordTitle }}</h1>
        </div>

        @if(!$inModal)
        <div class="aura-record-header-actions save-resource {{ $showSaveButton ? 'aura-record-header-actions-sm' : '' }}">
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
            @if ($formSections['card'] && ! $inModal)
                {{-- Plain inputs without a panel get the card a panel would give them (fields/panel). --}}
                <div class="px-2 w-full" data-form-card>
                    <div class="mb-8 aura-card">
                        <div class="mb-2">
                            <div class="flex flex-wrap items-start -mx-2 w-[calc(100%+1rem)]">
                                @foreach($formSections['fields'] as $key => $field)
                                    @checkCondition($model, $field, $form)
                                        <x-dynamic-component :component="$field['field']->edit()" :field="$field" wire:key="resource-field-{{ $key }}" :form="$form" :mode="$mode" />
                                    @endcheckCondition
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @else
           @foreach($formSections['fields'] as $key => $field)
            @checkCondition($model, $field, $form)
                    <x-dynamic-component :component="$field['field']->edit()" :field="$field" wire:key="resource-field-{{ $key }}" :form="$form" :mode="$mode" />
            @endcheckCondition
            @endforeach
            @endif
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

    @if (! $inModal && $showSaveButton)
        @include('aura::livewire.resource.form-bar', ['cancelUrl' => $indexUrl])
    @endif

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
