@php
    $storedSettings = isset($settings) && is_array($settings)
        ? $settings
        : (app('aura')::getOption('settings') ?? []);
    $settings = \Aura\Base\ThemeTokens::resolve($storedSettings);
    $fontStylesheet = \Aura\Base\ThemeTokens::fontStylesheet($settings);
@endphp

<style>[x-cloak] {
    display: none !important;
}</style>

@if ($fontStylesheet)
    <link rel="stylesheet" href="{{ asset($fontStylesheet) }}" data-aura-font>
@endif

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
