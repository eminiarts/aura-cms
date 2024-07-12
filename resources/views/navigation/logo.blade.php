@php
    $logo = $darkLogo = null;
    $settings = $this->settings;

    $compact = $this->compact;

    if($settings && (isset($settings['logo'][0]) || isset($settings['logo-darkmode'][0]))) {
        $logo = isset($settings['logo'][0]) ? $settings['logo'][0] : null;
        $darkLogo = isset($settings['logo-darkmode'][0]) ? $settings['logo-darkmode'][0] : null;
    } 
    // Debugging: Dump the logo variables
    // dump(['logo' => $logo, 'darkLogo' => $darkLogo, 'sidebarType' => $sidebarType]);

@endphp

@if($logo || $darkLogo)
    <div class="pr-3 {{ ($compact ? 'w-40' : 'w-48') }}">
    @if($logo && $darkLogo)
        <x-aura::image class="object-contain object-left w-full h-10 aura-sidebar-logo" :id="$logo"></x-aura::image>
        <x-aura::image class="object-contain object-left w-full h-10 aura-sidebar-logo-dark" :id="$darkLogo"></x-aura::image>
        {{-- @dump('1') --}}
    @elseif(isset($darklogo) && $darklogo)
        <x-aura::image class="object-contain object-left w-full h-10" :id="$darklogo" alt="{{ $settings['title'] ?? '' }}"></x-aura::image>
    @else
        <x-aura::image class="object-contain object-left w-full h-10" :id="$logo" alt="{{ $settings['title'] ?? '' }}"></x-aura::image>
    @endif
    </div>
@else
    @if($this->sidebarType == 'light')
        <div class="px-2 {{ ($this->compact ? 'w-28' : 'w-48') }}">
            <x-aura::application-logo class="w-full h-8 text-gray-700 fill-current dark:text-white" />
        </div>
    @else
        <div class="px-2 {{ ($this->compact ? 'w-28' : 'w-48') }}">
            <x-aura::application-logo class="w-full h-8 text-white fill-current dark:text-white" />
        </div>
    @endif
@endif
