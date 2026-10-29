<div class="toggleFilters">
    <x-aura::button.border size="sm" :class="$controlHeight ?? 'h-10'" @click="toggleFilters()">
    <x-slot:icon>
    <x-aura::icon icon="filter" />
</x-slot>
{{ __('Filters') }}
</x-aura::button.border>
</div>
