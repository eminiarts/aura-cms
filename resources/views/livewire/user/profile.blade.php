<div>
    @section('title', 'Profile • ')

    {{ app('aura')::injectView('profile_before_header') }}

    <div class="mb-6">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li title="Profile" />
        </x-aura::breadcrumbs>
    </div>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="mb-2 text-3xl font-semibold">Profile</h1>
        </div>

        <div>
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
    <x-aura::fields.conditions :field="$field" :model="$this">
        <div wire:key="resource-field-{{ $key }}"
        id="resource-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" />
        </div>
    </x-aura::fields.conditions>
    @endforeach

  
</div>
