@props(['cols'])

@php
    $colSpan = match($cols) {
        'full' => 'col-span-12',
        '6' => 'col-span-6',
        '4' => 'col-span-4',
        default => 'col-span-12'
    };
@endphp

{{-- The breadcrumb is hidden on phones, so the grid cell goes too (no empty row plus gap). --}}
<div {{ $attributes->merge(['class' => "hidden sm:block {$colSpan}"]) }}>
    <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
                iconClass="text-gray-500 w-6 h-6 mr-0" />

            <x-aura::breadcrumbs.li title="Dashboard" />
        </x-aura::breadcrumbs>
</div>
