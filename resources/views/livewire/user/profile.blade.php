<div>
    @section('title', 'Profile')

    {{ app('aura')::injectView('profile_before_header') }}

    {{-- Page header as on index pages: breadcrumb from sm, then the title row with the action. --}}
    <div class="hidden sm:block">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
            <x-aura::breadcrumbs.li title="{{ __('Profile') }}" />
        </x-aura::breadcrumbs>
    </div>

    <div class="flex gap-4 justify-between items-center mt-1 mb-6 sm:mt-6">
        <h1 class="aura-page-title">{{ __('Profile') }}</h1>

        <div class="shrink-0">
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


</div>
