<div class="p-6">
    <form wire:submit="save">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Invite User') }}</h2>

        <div class="flex flex-wrap">
            <div class="w-full">
                <div class="flex flex-wrap items-start -mx-4 mb-4">
                    @foreach ($this->fields as $key => $field)
                        <x-dynamic-component :component="$field['field']->edit()" mode="edit" :field="$field" :form="$form" />
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-4">
            <x-aura::button.transparent type="button" x-on:click="$dispatch('close-invite-modal')">
                {{ __('Cancel') }}
            </x-aura::button.transparent>

            <x-aura::button.primary type="submit">
                <div wire:loading wire:target="save">
                    <x-aura::icon.loading />
                </div>
                {{ __('Invite') }}
            </x-aura::button.primary>
        </div>
    </form>
</div>
