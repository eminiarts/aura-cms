@php
    $settings = app('aura')::getOption('settings') ?? [];
    $appSettings = app('aura')::options() ?? [];

    $settings = empty($settings) ? config('aura.theme') : $settings;
    $appSettings = empty($appSettings) ? config('aura.theme') : $appSettings;
@endphp

<style>[x-cloak] {
    display: none !important;
}</style>

<link rel="stylesheet" href="/vendor/aura/public/inter.css">

{{-- @vite(['resources/css/app.css'], 'vendor/aura') --}}

@if (view()->exists('components.layouts.aura-head'))
    @include('components.layouts.aura-head')
@endif

{{ app('aura')::viteStyles() }}

@stack('styles')

@include('aura::components.layout.colors')

<script>
    @if(optional($settings)['darkmode-type'] == 'dark')
    document.documentElement.classList.add('dark')
    @elseif (optional($settings)['darkmode-type'] == 'light')
    document.documentElement.classList.remove('dark')
    document.documentElement.classList.remove('light')
    @else
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.classList.add('dark')
    }
    @endif
</script>
