

<template x-if="selectionCount() > 0" key="bulk-select">

        <div class="flex z-[1] absolute bottom-0 left-1/2 justify-center w-full max-w-2xl transform -translate-x-1/2 ">
            <div class="px-4 py-3 mb-6 w-full text-sm text-gray-800 rounded-xl ring-1 shadow-lg backdrop-blur-md transform bg-white/85 ring-gray-950/10 dark:bg-gray-800/85 dark:ring-white/10 dark:text-gray-200">

            <template x-if="!selectAll">
                <div class="flex justify-between items-center">
                    <div>
                        <template x-if="selected">
                            <span>
                                {{ __('You have selected') }} <strong x-text="selectionCount()"></strong>
                                <span x-text="selectionCount() === 1 ? '{{ __('row') }}' : '{{ __('rows') }}'"></span>.
                            </span>
                        </template>

                        @if (! $field)
                            <x-aura::button.border size="xs" x-on:click="selectAllRows">
                                {{ __('Select all') }}
                            </x-aura::button.border>
                        @endif
                    </div>

                    <x-aura::button.border size="xs" x-on:click="resetBulk();">
                        {{ __('Clear selection') }}
                    </x-aura::button.border>
                </div>
            </template>

            <template x-if="selectAll" key="select-all">
                <div class="flex justify-between items-center">
                    <span>
                        {{ __('You have selected all') }}
                        <strong x-text="selectionCount()"></strong>
                        <span x-text="selectionCount() === 1 ? '{{ __('row') }}' : '{{ __('rows') }}'"></span>.
                    </span>

                    <x-aura::button.border size="xs" x-on:click="resetBulk();">
                        {{ __('Clear selection') }}
                    </x-aura::button.border>
                </div>
            </template>
            </div>
        </div>
    </template>
