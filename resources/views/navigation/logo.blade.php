@php
    $logo = $darkLogo = null;

    if($settings && (isset($settings['app-logo'][0]) || isset($settings['app-logo-darkmode'][0]))) {
        $logo = isset($settings['app-logo'][0]) ? $settings['app-logo'][0] : null;
        $darkLogo = isset($settings['app-logo-darkmode'][0]) ? $settings['app-logo-darkmode'][0] : null;
    } elseif($appSettings && (isset($appSettings['app-logo'][0]) || isset($appSettings['app-logo-darkmode'][0]))) {
        $logo = isset($appSettings['app-logo'][0]) ? $appSettings['app-logo'][0] : null;
        $darkLogo = isset($appSettings['app-logo-darkmode'][0]) ? $appSettings['app-logo-darkmode'][0] : null;
    }
@endphp

@if($logo || $darkLogo)
    @if($sidebarType == 'light' && $logo && $darkLogo)
        <img class="block h-10 dark:hidden" src="{{ asset('storage/' . app('aura')::getPath($logo)) }}" alt="{{ $settings['title'] ?? '' }}">
        <img class="hidden h-10 dark:block" src="{{ asset('storage/' . app('aura')::getPath($darkLogo)) }}" alt="{{ $settings['title'] ?? '' }}">
    @else
        <img class="h-10" src="{{ asset('storage/' . app('aura')::getPath($darkLogo ? $darkLogo : $logo)) }}" alt="{{ $settings['title'] ?? '' }}">
    @endif
@else
    @if($sidebarType == 'light')
        <x-aura::application-logo class="h-8 text-gray-700 fill-current dark:text-white" />
    @else
        <x-aura::application-logo class="h-8 text-white fill-current dark:text-white" />
    @endif
@endif
