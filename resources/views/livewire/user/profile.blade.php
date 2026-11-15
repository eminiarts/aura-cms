{{-- The profile is a form: one readable, left-aligned column (max-w-3xl) as on the resource forms,
     breadcrumb and header included, so Save ends at the form's right edge. --}}
<div class="max-w-3xl" data-form-body>
    @section('title', 'Profile')

    {{ app('aura')::injectView('profile_before_header') }}

    {{-- Page header as on index pages. The profile is a top-level page: no trail, the bookmark beside the title. --}}
    <div class="flex gap-4 justify-between items-center mt-1 mb-6 sm:mt-0 sm:mb-8 sm:min-h-[3.375rem] sm:items-end">
        <div class="flex gap-1 items-center min-w-0">
            <h1 class="aura-page-title">{{ __('Profile') }}</h1>
            <x-aura::page-bookmark />
        </div>

        {{-- Phones save from the bar on the bottom edge, as on resource forms and settings. --}}
        <div class="shrink-0 max-sm:hidden">
            <x-aura::button wire:click="save">
                <div wire:loading wire:target="save">
                    <x-aura::icon.loading  />
                </div>
                {{ __('Save') }}
            </x-aura::button>
        </div>
    </div>

    {{ app('aura')::injectView('profile_after_header') }}

    <x-aura::validation-errors />

    {{-- Same field gutter as the edit page: sections bring px-2, so tabs and cards line up with the title. --}}
    <div class="-mx-2">
    @foreach($this->fieldsForView as $key => $field)
    <style>
        #resource-field-{{ optional($field)['slug'] }}-wrapper {
            width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
        }

        @media screen and (max-width: 768px) {
            #resource-field-{{ optional($field)['slug'] }}-wrapper {
                width: 100%;
            }
        }
    </style>
    <x-aura::fields.conditions :field="$field" :model="$this->model">
        <div wire:key="resource-field-{{ $key }}" class="w-full"
        id="resource-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" mode="edit" />
        </div>
    </x-aura::fields.conditions>
    @endforeach
    </div>

    @include('aura::livewire.resource.form-bar', ['cancelUrl' => null])
</div>
