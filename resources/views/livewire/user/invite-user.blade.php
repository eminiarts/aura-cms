<div class="">

    <form wire:submit="save">
        <x-aura::dialog.title>{{ __('Invite User') }}</x-aura::dialog.title>

        <div class="flex flex-wrap">

            <div class="w-full">
                <div class="mb-4 flex flex-wrap items-start -mx-4">
                    @foreach ($this->fields as $key => $field)
                        
                            <x-dynamic-component :component="$field['field']->component" :field="$field" :form="$form" />
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
