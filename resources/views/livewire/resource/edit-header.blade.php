@if (!$inModal)
@php
    // Cancel leaves the form for the record view, or for the list when the record has no view page.
    // edit.blade.php computes it for the phone form bar too; the fallback keeps this header self-contained.
    $cancelUrl ??= (Route::has('aura.' . $model->getSlug() . '.view') && auth()->user()?->can('view', $model))
        ? $model->viewUrl()
        : (Route::has('aura.' . $model->getSlug() . '.index') ? route('aura.' . $model->getSlug() . '.index') : null);
@endphp
{{-- Same header as the record view: "Edit company" as eyebrow, record title as H1 (up to two lines on
     phones), actions to the right. On phones Cancel and Save move into the bottom form bar; only
     resource actions, if any, stay as a right-aligned row under the title. --}}
<div class="mt-1 mb-6 aura-record-block sm:my-8">
<div class="aura-record-header">
    <div class="flex flex-1 gap-3 items-center min-w-0 sm:min-w-[10rem]">
        {{ app('aura')::injectView('post_edit_title_before') }}
        @if ($model->avatarShape())
            <x-aura::avatar :record="$model" size="lg" />
        @endif
        <div class="min-w-0">
            <p class="aura-eyebrow">{{ __('Edit :resource', ['resource' => \Aura\Base\RecordLayout\RecordFormSections::inSentence(__($model->singularName()))]) }}</p>
            <h1 class="mt-0.5 break-words aura-page-title line-clamp-2 sm:line-clamp-1" title="{{ $model->title() }}">{{ $model->title() }}</h1>
        </div>
        {{ app('aura')::injectView('post_edit_title_after') }}
    </div>

    <div @class(['aura-record-header-actions', 'aura-record-header-actions-sm' => ! count($this->actions)])>

        @include('aura::livewire.resource.actions')

        @if ($cancelUrl)
            <x-aura::button.transparent :href="$cancelUrl" class="hidden sm:inline-flex">
                {{ __('Cancel') }}
            </x-aura::button.transparent>
        @endif

        <div class="hidden sm:block save-resource">
            <x-aura::button wire:click="save" wire:loading.attr="disabled">
                <div wire:loading.delay wire:target="save">
                    <x-aura::icon.loading/>
                </div>
                {{ __('Save') }}
            </x-aura::button>
        </div>
    </div>
</div>
</div>
@else
<x-aura::dialog.title>{{ __('Edit ' . $model->singularName()) }}</x-aura::dialog.title>
@endif
