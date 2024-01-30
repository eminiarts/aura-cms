<div>
    <x-aura::button.border @click="toggleFilters()">
    <x-slot:icon>
    <x-aura::icon icon="filter" />
</x-slot>
{{ __('Filters') }}
</x-aura::button.border>
</div>