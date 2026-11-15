@php
    $logo = $darkLogo = null;
    $settings = $this->settings;

    $compact = $this->compact;

    if($settings && (isset($settings['logo'][0]) || isset($settings['logo-darkmode'][0]))) {
        $logo = isset($settings['logo'][0]) ? $settings['logo'][0] : null;
        $darkLogo = isset($settings['logo-darkmode'][0]) ? $settings['logo-darkmode'][0] : null;
    }
    // Debugging: Dump the logo variables

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
        <div class="{{ $this->compact ? 'px-2 w-28' : 'pl-3 w-[8.25rem]' }}">
            <x-aura::application-logo class="w-full {{ $this->compact ? 'h-8' : 'h-auto' }} text-gray-700 fill-current" />
        </div>
    @else
        <div class="{{ $this->compact ? 'px-2 w-28' : 'pl-3 w-[8.25rem]' }}">
            <x-aura::application-logo class="w-full {{ $this->compact ? 'h-8' : 'h-auto' }} text-white fill-current dark:text-white" />
        </div>
    @endif
@endif
