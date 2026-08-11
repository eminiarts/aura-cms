<div class="relative" wire:key="aura-saved-views">
    <x-aura::dropdown align="right" width="60" :closeOnSelect="false">
        <x-slot name="trigger">
            <x-aura::button.border>{{ __('Views') }}</x-aura::button.border>
        </x-slot>

        <x-slot name="content">
            <div class="w-72 p-3 space-y-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="aura-saved-view-select">
                    {{ __('Saved view') }}
                </label>
                <select id="aura-saved-view-select" class="w-full rounded-md border-gray-300 text-sm dark:bg-gray-800 dark:text-gray-100"
                    wire:change="applySavedView($event.target.value)">
                    <option value="">{{ __('Current view') }}</option>
                    @foreach ($this->savedViews as $savedView)
                        <option wire:key="saved-view-{{ $savedView->getKey() }}" value="{{ $savedView->getKey() }}" @selected((string) $savedViewId === (string) $savedView->getKey())>
                            {{ $savedView->name }}@if ($savedView->visibility === \Aura\Base\SavedViews\SavedViewVisibility::Team) — {{ __('Shared') }}@endif
                        </option>
                    @endforeach
                </select>

                <x-aura::input.text wire:model="savedViewName" :placeholder="__('View name')" />

                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <x-aura::input.checkbox wire:model="savedViewShared" />
                    <span>{{ __('Share with team') }}</span>
                </label>

                <div class="flex flex-wrap gap-2">
                    <x-aura::button.primary wire:click="saveCurrentView">{{ __('Save') }}</x-aura::button.primary>
                    @if ($savedViewId !== null)
                        <x-aura::button.border wire:click="renameSavedView">{{ __('Rename') }}</x-aura::button.border>
                        <x-aura::button.border wire:click="duplicateSavedView">{{ __('Duplicate') }}</x-aura::button.border>
                        <x-aura::button.border wire:click="setSavedViewDefault">{{ __('Set default') }}</x-aura::button.border>
                        <x-aura::button.danger wire:click="deleteSavedView" wire:confirm="{{ __('Delete this saved view?') }}">{{ __('Delete') }}</x-aura::button.danger>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-aura::dropdown>
</div>
