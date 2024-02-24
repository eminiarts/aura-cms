@php
    $settings = app('aura')::getOption('team-settings');
    $appSettings = app('aura')::options();
@endphp

@include('aura::components.layout.colors')

<style>[x-cloak] {
    display: none !important;
}</style>

<link rel="stylesheet" href="/vendor/aura/public/inter.css">

@vite(['resources/css/app.css'], 'vendor/aura')

@stack('styles')
