@php
    $columns = ['Title' => true, 'Status' => true, 'Author' => false, 'Created at' => true];
@endphp

<div class="grid gap-4 mt-6 sm:grid-cols-2">
    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Popover (dropdown, closeOnSelect=false)') }}</span>
        <div class="mt-4">
            <x-aura::dropdown align="left" width="w-60" :closeOnSelect="false">
                <x-slot:trigger>
                    <x-aura::button.border>
                        <x-slot:icon><x-aura::icon icon="table" size="sm" /></x-slot:icon>
                        {{ __('Columns') }}
                    </x-aura::button.border>
                </x-slot:trigger>
                <x-slot:content>
                    <div class="p-1">
                        @foreach ($columns as $column => $visible)
                            <label class="flex gap-2 items-center px-2 py-1.5 rounded-md transition-colors duration-150 cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5">
                                <x-aura::input.checkbox :checked="$visible" hideLabel :label="$column" />
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ __($column) }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-slot:content>
            </x-aura::dropdown>
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Used for the table column picker (table/settings). Aura has no dedicated popover component.') }}</p>
    </div>

    <div class="aura-card" data-popover-specimen="open">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Open popover (static)') }}</span>
        <div class="mt-4 w-60 rounded-lg shadow-lg">
            <div class="p-1 bg-white rounded-lg ring-1 ring-gray-950/10 dark:ring-white/10 dark:bg-gray-800 dark:text-gray-200">
                @foreach ($columns as $column => $visible)
                    <div @class(['sg-force-hover' => $loop->index === 1])>
                        <label class="flex gap-2 items-center px-2 py-1.5 rounded-md transition-colors duration-150 cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5">
                            <x-aura::input.checkbox :checked="$visible" hideLabel :label="$column" />
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ __($column) }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Tooltip (x-aura::tippy)') }}</span>
        <div class="flex gap-6 items-center mt-4">
            <x-aura::tippy text="Tooltip on top" position="top">
                <x-aura::button.border>{{ __('Hover me') }}</x-aura::button.border>
            </x-aura::tippy>
            <x-aura::tippy text="More information about this field" position="right">
                <span class="inline-flex items-center text-gray-400 cursor-help">
                    <x-aura::icon icon="info" size="sm" />
                </span>
            </x-aura::tippy>

            {{-- Static tippy box with the real tippy + aura theme CSS --}}
            <div class="tippy-box" data-theme="aura" data-placement="top" data-tooltip-specimen="tippy">
                <div class="tippy-content">{{ __('Tooltip on top') }}</div>
            </div>
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Used for field help and row actions. Appended to <body>, outside this page.') }}</p>
    </div>

    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Tooltip (x-aura::tooltip, CSS only)') }}</span>
        <div class="flex gap-10 items-center pt-10 mt-4">
            <x-aura::tooltip text="Hover tooltip" position="top">
                <x-aura::button.border>{{ __('Hover me') }}</x-aura::button.border>
            </x-aura::tooltip>
            <div class="sg-force-hover" data-tooltip-specimen="css">
                <x-aura::tooltip text="Forced hover" position="top">
                    <x-aura::button.border>{{ __('Hover state') }}</x-aura::button.border>
                </x-aura::tooltip>
            </div>
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Two tooltip styles exist: tippy (aura theme) and this one (bg-gray-900/95, rounded-md). Used in input/wrapper help.') }}</p>
    </div>
</div>
