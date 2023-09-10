

<template x-if="selected && selected.length > 0" key="bulk-select">

        <div class="p-2 bg-primary-50 dark:bg-gray-900 dark:text-white text-gray-900 rounded-lg text-sm">

            <template x-if="!selectAll">
                <div>
                    <template x-if="selected">
                        <span>
                            {{ __('You have selected') }} <strong x-text="selected.length"></strong>
                            <span x-text="selected.length === 1 ? '{{ __('row') }}' : '{{ __('rows') }}'"></span>.
                        </span>
                    </template>

                    <button x-on:click="selectAllRows" type="button"
                        class="ml-1 text-sm font-medium leading-5 text-gray-700 text-primary-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400">
                        {{ __('Select all') }} 
                    </button>

                    <button x-on:click="resetBulk();" type="button"
                        class="ml-1 text-sm font-medium leading-5 text-gray-700 text-primary-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400">
                        {{ __('Unselect All') }}
                    </button>
                </div>
            </template>

            <template x-if="selectAll" key="select-all">
                <div>
                    <span>
                        {{ __('You have selected all') }}
                        {{-- <strong x-text="selected.length"></strong> --}}
                    </span>

                    <button x-on:click="resetBulk()" type="button"
                        class="ml-1 text-sm font-medium leading-5 text-gray-700 text-primary-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400">
                        {{ __('Unselect All') }}
                    </button>
                </div>
            </template>
        </div>
    </template>
