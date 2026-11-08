@if (!$inModal)
{{-- Same header as the record view: resource name as eyebrow, record title as H1 (up to two lines on
     phones), actions to the right. They wrap below only when they leave the title less than 10rem. --}}
<div class="flex flex-wrap gap-x-4 gap-y-3 justify-between items-center mt-1 mb-6 sm:flex-nowrap sm:my-8">
    <div class="flex flex-1 gap-3 items-center min-w-[10rem]">
        {{ app('aura')::injectView('post_edit_title_before') }}
        @if ($model->avatarShape())
            <x-aura::avatar :record="$model" size="lg" />
        @endif
        <div class="min-w-0">
            <p class="aura-eyebrow">{{ __($model->singularName()) }}</p>
            <h1 class="mt-0.5 break-words aura-page-title line-clamp-2 sm:line-clamp-1" title="{{ $model->title() }}">{{ $model->title() }}</h1>
        </div>
        {{ app('aura')::injectView('post_edit_title_after') }}
    </div>

    <div class="flex gap-2 items-center shrink-0">

        @include('aura::livewire.resource.actions')

        @can('view', $model)
            @if(Route::has('aura.' . $model->getSlug() . '.view'))
                <a href="{{ $model->viewUrl() }}" class="text-gray-500 hover:text-gray-700">
                    <x-aura::button.transparent>
                        {{-- <x-aura::icon.view class="w-5 h-5 mr-2"/> --}}
                        {{ __('View') }}
                    </x-aura::button.transparent>
                </a>
            @endif
        @endcan

        <div class="save-resource">
            <x-aura::button wire:click="save" wire:loading.attr="disabled">
                <div wire:loading.delay wire:target="save">
                    <x-aura::icon.loading/>
                </div>
                {{ __('Save') }}
            </x-aura::button>
        </div>
    </div>
</div>
@else
<x-aura::dialog.title>{{ __('Edit ' . $model->singularName()) }}</x-aura::dialog.title>
@endif
