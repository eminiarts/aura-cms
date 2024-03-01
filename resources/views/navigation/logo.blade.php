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
        <x-aura::image class="block object-contain object-left w-48 h-10 dark:hidden" :id="$logo"></x-aura::image>
        <x-aura::image class="hidden object-contain object-left w-48 h-10 dark:block" :id="$darkLogo"></x-aura::image>
    @else
        <x-aura::image class="object-contain object-left w-48 h-10" :id="$logo" alt="{{ $settings['title'] ?? '' }}"></x-aura::image>
    @endif
@else
    @if($sidebarType == 'light')
        <x-aura::application-logo class="h-8 text-gray-700 fill-current dark:text-white" />
    @else
        <x-aura::application-logo class="h-8 text-white fill-current dark:text-white" />
    @endif
@endif
