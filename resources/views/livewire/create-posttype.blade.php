<div class="p-8">
    <h2 class="text-3xl font-semibold">{{ __('Create Resource') }}</h2>
    @if (count($errors->all()))
        <div class="block">
            <div class="mt-8 form_errors">
                <strong class="block text-red-600">
                    {{ __('Unfortunately, there were still the following validation errors:') }}
                </strong>
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

    <div class="-mx-4 mb-4">
        @foreach($this->fields as $key => $field)
        <style nonce="{{ csp_nonce() }}">
            #post-field-{{ optional($field)['slug'] }}-wrapper {
                width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
            }

            @media screen and (max-width: 768px) {
                #post-field-{{ optional($field)['slug'] }}-wrapper {
                width: 100%;
                }
            }
        </style>
        <div wire:key="post-field-{{ $key }}"
        id="post-field-{{ optional($field)['slug'] }}-wrapper">
            <x-dynamic-component :component="$field['field']->component" :field="$field" />
        </div>
        @endforeach
    </div>

    <x-aura::button size="xl" wire:click="save">
        <div wire:loading>
            <x-aura::icon.loading  />
        </div>
        {{ __('Save') }}
    </x-aura::button>

</div>
