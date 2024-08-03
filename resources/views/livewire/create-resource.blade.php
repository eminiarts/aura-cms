<div>
    <x-aura::dialog.title>{{ __('Create Resource') }}</x-aura::dialog.title>

    <x-aura::validation-errors />

    <form wire:submit="save">
        <div class="-mx-4 mb-4">
            @foreach ($this->fields as $key => $field)
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
                <div wire:key="resource-field-{{ $key }}"
                    id="resource-field-{{ optional($field)['slug'] }}-wrapper">
                    <x-dynamic-component :component="$field['field']->component()" :field="$field" :form="$form" />
                </div>
            @endforeach
        </div>

     

        <x-aura::dialog.footer>
            <x-aura::dialog.close>
                <x-aura::button.transparent>
                    {{ __('Cancel') }}
                </x-aura::button.transparent>
            </x-aura::dialog.close>

            <x-aura::button.primary type="submit">

                <div wire:loading wire:target="save">
                    <x-aura::icon.loading />
                </div>
                {{ __('Save') }}
            </x-aura::button.primary>
        </x-aura::dialog.footer>
    </form>



</div>
