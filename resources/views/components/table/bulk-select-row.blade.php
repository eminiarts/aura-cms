

<template x-if="selected && selected.length > 0" key="bulk-select">

        <div class="flex z-[1] absolute bottom-0 left-1/2 justify-center w-full max-w-2xl transform -translate-x-1/2 ">
            <div class="px-6 py-4 mb-6 w-full text-sm text-gray-800 rounded-xl border border-gray-200 shadow-lg backdrop-blur-sm transform bg-white/70">

            <template x-if="!selectAll">
                <div class="flex justify-between items-center">
                    <div>
                        <template x-if="selected">
                            <span>
                                {{ __('You have selected') }} <strong x-text="selected.length"></strong>
                                <span x-text="selected.length === 1 ? '{{ __('row') }}' : '{{ __('rows') }}'"></span>.
                            </span>
                        </template>

                        <x-aura::button.border size="xs" x-on:click="selectAllRows">
                            {{ __('Select all') }}
                        </x-aura::button.border>
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
                        <strong x-text="selected.length"></strong>
                        <span x-text="selected.length === 1 ? '{{ __('row') }}' : '{{ __('rows') }}'"></span>.
                    </span>

                    <x-aura::button.border size="xs" x-on:click="resetBulk();">
                        {{ __('Clear selection') }}
                    </x-aura::button.border>
                </div>
            </template>
            </div>
        </div>
    </template>
