<div>
    @section('title', 'Profile • ')

    {{ app('aura')::injectView('profile_before_header') }}

    <div>
        <h1 class="text-3xl font-semibold">{{ __('Profile') }}</h1>
        <h3> {{ __('Update your account\'s profile information and email address.') }}</h3>
    </div>

    {{ app('aura')::injectView('profile_after_header') }}

    @if (count($errors->all()))
            <div class="block">
                <div class="mt-8 form_errors">
                    <strong class="block text-red-600">{{ __('Unfortunately, there were still the following validation errors:') }}</strong>
                    <div class="text-red-600 prose">
                        <ul>
                            @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

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
</div>
