<div class="grid gap-4 mt-6 sm:grid-cols-2">
    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Interactive') }}</span>
        <div class="mt-4">
            <x-aura::dropdown align="left" width="48">
                <x-slot:trigger>
                    <x-aura::button.border>
                        <span>{{ __('Actions') }}</span>
                        <x-aura::icon icon="chevron-down" size="xs" />
                    </x-aura::button.border>
                </x-slot:trigger>
                <x-slot:content>
                    <x-aura::dropdown-link href="#menus">{{ __('Edit') }}</x-aura::dropdown-link>
                    <x-aura::dropdown-link href="#menus">{{ __('Duplicate') }}</x-aura::dropdown-link>
                    <x-aura::dropdown-link href="#menus">{{ __('Delete') }}</x-aura::dropdown-link>
                </x-slot:content>
            </x-aura::dropdown>
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Props: align (left, right, top), width (48 or a class), contentClasses, closeOnSelect.') }}</p>
    </div>

    <div class="aura-card" data-menu-specimen="open">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Open panel (static)') }}</span>
        {{-- Same wrapper classes as the open panel in dropdown.blade.php --}}
        <div class="mt-4 w-48 rounded-lg shadow-lg">
            <div class="p-1 bg-white rounded-lg ring-1 ring-gray-950/10 dark:ring-white/10 dark:bg-gray-800 dark:text-gray-200">
                <x-aura::dropdown-link href="#menus">{{ __('Edit') }}</x-aura::dropdown-link>
                <div class="sg-force-hover">
                    <x-aura::dropdown-link href="#menus">{{ __('Duplicate (hover)') }}</x-aura::dropdown-link>
                </div>
                <x-aura::dropdown-link href="#menus">{{ __('Delete') }}</x-aura::dropdown-link>
            </div>
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('The panel uses rounded-lg, not --aura-control-radius or --aura-panel-radius. There is no separator or danger item style.') }}</p>
    </div>
</div>
