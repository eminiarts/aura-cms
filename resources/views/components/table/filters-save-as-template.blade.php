<x-aura::dialog wire:model="showSaveFilterModal">
    <x-aura::dialog.open>
        <x-aura::button.transparent size="xs" wire:click="$set('showSaveFilterModal', true)" class="mt-4">
            {{ __('Save Filter as Template') }}
        </x-aura::button.transparent>
    </x-aura::dialog.open>

    <x-aura::dialog.panel>
        <x-aura::dialog.title>{{ __('Save Filter') }}</x-aura::dialog.title>

        <div class="mt-5 text-gray-600">
            <div>
                <x-aura::input.wrapper label="Filter Name*" error="filter.name" :help="__('Enter a Filter Name')">
                    <x-aura::input.text required wire:model="filter.name" error="filter.name" :placeholder="__('Enter a Name for the Filter')"></x-aura::input.text>
                </x-aura::input.wrapper>

                <x-aura::input.wrapper label="Icon" error="filter.icon" :help="__('Icon (optional)')">
                    <x-aura::input.text required wire:model="filter.icon" error="filter.icon" :placeholder="__('Icon (optional)')"></x-aura::input.text>
                </x-aura::input.wrapper>
            </div>

            <div class="mt-4">
                <x-aura::input.wrapper label="{{ __('Table Tabs') }}" error="filter.global" :help="__('Show above the Table of the Index Page')">
                    <x-aura::input.toggle wire:model="filter.global" error="filter.global" label-after="Add this filter to the Tabs of the Index Page"></x-aura::input.toggle>
                </x-aura::input.wrapper>

                <x-aura::input.wrapper label="{{ __('Public Filter') }}" error="filter.public" :help="__('Make this filter available for everyone')">
                    <x-aura::input.toggle wire:model="filter.public" error="filter.public" label-after="Make this filter available for everyone"></x-aura::input.toggle>
                </x-aura::input.wrapper>
            </div>
        </div>

         <x-aura::dialog.footer>
                <x-aura::dialog.close>
                    <x-aura::button.transparent>
                        {{ __('Cancel') }}
                    </x-aura::button.transparent>
                </x-aura::dialog.close>

                <x-aura::button.primary wire:click="saveFilter">{{ __('Save as Template') }}</x-aura::button.primary>
            </x-aura::dialog.footer>
    </x-aura::dialog.panel>
</x-aura::dialog>
