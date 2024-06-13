<div class="">

    <form wire:submit="save">
        <x-aura::dialog.title>{{ __('Invite User') }}</x-aura::dialog.title>

        <div class="flex flex-wrap">

            <div class="w-full pr-4">
                <div class="mb-4 -mx-4">
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
                            <x-dynamic-component :component="$field['field']->component" :field="$field" :form="$form" />
                        </div>
                    @endforeach
                </div>
            </div>
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
                {{ __('Invite') }}
            </x-aura::button.primary>
        </x-aura::dialog.footer>

    </form>

</div>
