@props(['cols'])

@php
    $colSpan = match($cols) {
        'full' => 'col-span-12',
        '6' => 'col-span-6',
        '4' => 'col-span-4',
        default => 'col-span-12'
    };
@endphp

<div {{ $attributes->merge(['class' => "{$colSpan}"]) }}>
    <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
                iconClass="text-gray-500 w-7 h-7 mr-0" />

            <x-aura::breadcrumbs.li title="Dashboard" />
        </x-aura::breadcrumbs>
</div>
