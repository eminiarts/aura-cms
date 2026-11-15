@php
    // Phones (the index toolbar passes phoneMenu): 'actions' hides the trigger (the empty wrapper gives its
    // toolbar gap back), the actions menu opens the panel through its "Columns" item (aura-table-columns); 'more' draws the trigger as the
    // "more" button at the end of the row, the panel titled "Columns". Without it the button looks the same everywhere.
    $phoneMenu ??= null;
@endphp
<div id="table-columns-button" @class(['relative close-on-select-false', 'max-sm:w-0 max-sm:-mr-2' => $phoneMenu === 'actions', 'max-sm:order-last' => $phoneMenu === 'more'])>
    <x-aura::dropdown align="right" width="60" :closeOnSelect="false">
        <x-slot name="trigger">
            <div x-on:aura-table-columns.window="open = true">
            <x-aura::button.border :class="($controlHeight ?? '') . ' max-sm:w-9 max-sm:px-0 max-sm:[&>span:first-child]:ml-0' . ($phoneMenu === 'actions' ? ' max-sm:!hidden' : '')" title="{{ __('Columns') }}" aria-label="{{ __('Columns') }}">
                <x-slot:icon>
                    @if ($phoneMenu === 'more')
                        <x-aura::icon.dots class="sm:hidden" />
                    @endif
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" @class(['size-6', 'max-sm:hidden' => $phoneMenu === 'more'])> <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" /> </svg>
                </x-slot>
                <span class="sr-only sm:not-sr-only">{{ __('Columns') }}</span>
            </x-aura::button.border>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="w-60">
                @if ($phoneMenu)
                    <p class="px-4 pt-3 pb-1 text-xs font-medium text-gray-500 sm:hidden dark:text-gray-400">{{ __('Columns') }}</p>
                @endif
                <div class="p-2 sortable-wrapper" drag-root="reorder" role="none">

                    @if($this->headers)
                    @foreach($this->headers as $key => $label)
                    <label class="flex gap-2 items-center px-2 py-1.5 rounded-md transition-colors duration-150 cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5 sortable"
                        for="column_{{$key}}" id="{{ $key }}">

                        <x-aura::input.checkbox wire:model.live="columns.{{ $key }}" value="true" id="column_{{$key}}" />

                        <span class="flex flex-1 items-center text-sm text-gray-700 dark:text-gray-200 group" role="menuitem"
                            tabindex="-1" id="menu-item-6">
                            {{ __($label) }}
                        </span>

                        @if($this->settings['sort_columns'])
                        <div class="cursor-move drag-handle move-table-row">
                            <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 8.5H21M3 15.5H21" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        @endif

                    </label>
                    @endforeach
                    @endif

                    <div class="flex justify-between items-center px-2 pt-3 mt-2 border-t border-gray-100 dark:border-white/10">
                        <x-aura::label value="{{ __('Per page') }}" />
                        <x-aura::input.group borderless inline paddingless for="perPage" label="">
                            <x-aura::input.select
                                wire:model.live="perPage"
                                id="perPage"
                                :options="[
                                    10 => '10',
                                    25 => '25',
                                    50 => '50',
                                    100 => '100'
                                ]"
                            />
                        </x-aura::input.group>
                    </div>

                </div>
            </div>
        </x-slot>
    </x-aura::dropdown>
</div>
