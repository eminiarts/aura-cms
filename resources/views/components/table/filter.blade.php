<div class="toggleFilters">
    <x-aura::button.border size="sm" @click="toggleFilters()">
    <x-slot:icon>
    <x-aura::icon icon="filter" />
</x-slot>
{{ __('Filters') }}
</x-aura::button.border>
</div>