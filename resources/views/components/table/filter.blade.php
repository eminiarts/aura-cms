<div class="toggleFilters">
    <x-aura::button.border :class="($controlHeight ?? '') . ' max-sm:w-9 max-sm:px-0 max-sm:[&>span:first-child]:ml-0'" title="{{ __('Filters') }}" @click="toggleFilters()">
        <x-slot:icon>
            <x-aura::icon icon="filter" />
        </x-slot>
        <span class="sr-only sm:not-sr-only">{{ __('Filters') }}</span>
    </x-aura::button.border>
</div>
