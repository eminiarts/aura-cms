@php
    $logo = $darkLogo = null;

    if($settings && (isset($settings['app-logo'][0]) || isset($settings['app-logo-darkmode'][0]))) {
        $logo = isset($settings['app-logo'][0]) ? $settings['app-logo'][0] : null;
        $darkLogo = isset($settings['app-logo-darkmode'][0]) ? $settings['app-logo-darkmode'][0] : null;
    } elseif($appSettings && (isset($appSettings['app-logo'][0]) || isset($appSettings['app-logo-darkmode'][0]))) {
        $logo = isset($appSettings['app-logo'][0]) ? $appSettings['app-logo'][0] : null;
        $darkLogo = isset($appSettings['app-logo-darkmode'][0]) ? $appSettings['app-logo-darkmode'][0] : null;
    }

    // Debugging: Dump the logo variables
    // dump(['logo' => $logo, 'darkLogo' => $darkLogo, 'sidebarType' => $sidebarType]);

@endphp

@if($logo || $darkLogo)
    @if($logo && $darkLogo)
        <x-aura::image class="object-contain object-left w-48 h-10 aura-sidebar-logo" :id="$logo"></x-aura::image>
        <x-aura::image class="object-contain object-left w-48 h-10 aura-sidebar-logo-dark" :id="$darkLogo"></x-aura::image>
        {{-- @dump('1') --}}
    @elseif(isset($darklogo) && $darklogo)
        <x-aura::image class="object-contain object-left w-48 h-10" :id="$darklogo" alt="{{ $settings['title'] ?? '' }}"></x-aura::image>
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
