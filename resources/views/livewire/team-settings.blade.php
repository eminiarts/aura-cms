<div>
    @section('title', 'Edit Settings • ')

    <div>
        <h1 class="text-3xl font-semibold">Theme Options</h1>
        <h3>Configure Logo and Theme. These Settings are applied for this team</h3>
    </div>

    {{-- @dump($this->fieldsForView) --}}

    @foreach($this->fieldsForView as $key => $field)
    <style >
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
        <div wire:key="post-field-{{ $key }}"
        id="post-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->component" :field="$field" />
        </div>
    </x-aura::fields.conditions>
    @endforeach

    <x-aura::button size="xl" wire:click="save">
        <div wire:loading>
            <x-aura::icon.loading  />
        </div>
        {{ __('Save') }}
    </x-aura::button>

    {{-- <div class="flex mt-4 space-x-4">
        <div class="w-12 h-12 rounded bg-sidebar-bg"></div>
    </div>

    <div class="flex mt-4 space-x-4">
        <div class="w-12 h-12 rounded bg-primary-50"></div>
        <div class="w-12 h-12 rounded bg-primary-100"></div>
        <div class="w-12 h-12 rounded bg-primary-200"></div>
        <div class="w-12 h-12 rounded bg-primary-300"></div>
        <div class="w-12 h-12 rounded bg-primary-400"></div>
        <div class="w-12 h-12 rounded bg-primary-500"></div>
        <div class="w-12 h-12 rounded bg-primary-600"></div>
        <div class="w-12 h-12 rounded bg-primary-700"></div>
        <div class="w-12 h-12 rounded bg-primary-800"></div>
        <div class="w-12 h-12 rounded bg-primary-900"></div>
    </div>

    <div class="flex mt-4 space-x-4">
        <div class="w-12 h-12 bg-gray-50 rounded"></div>
        <div class="w-12 h-12 bg-gray-100 rounded"></div>
        <div class="w-12 h-12 bg-gray-200 rounded"></div>
        <div class="w-12 h-12 bg-gray-300 rounded"></div>
        <div class="w-12 h-12 bg-gray-400 rounded"></div>
        <div class="w-12 h-12 bg-gray-500 rounded"></div>
        <div class="w-12 h-12 bg-gray-600 rounded"></div>
        <div class="w-12 h-12 bg-gray-700 rounded"></div>
        <div class="w-12 h-12 bg-gray-800 rounded"></div>
        <div class="w-12 h-12 bg-gray-900 rounded"></div>
    </div> --}}
</div>
