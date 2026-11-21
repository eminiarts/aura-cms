@if(\Aura\Base\Facades\Aura::area() === 'global')
    {{-- Leaving the area is a plain link: the URL alone decides the area. --}}
    <div class="flex gap-3 items-center px-2 min-w-0 {{ $this->compact ? 'w-40' : 'w-48' }}">
        <div class="flex justify-center items-center w-8 h-8 text-gray-900 bg-yellow-400 rounded-lg shrink-0">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
            </svg>
        </div>
        <div class="min-w-0 leading-tight">
            <p class="text-sm font-semibold truncate aura-sidebar-user-name">{{ __('Global Admin') }}</p>
            @if(! config('aura.teams'))
                <a href="{{ route('aura.dashboard') }}" class="block mt-0.5 text-xs truncate aura-sidebar-team-name hover:underline">&larr; {{ __('Back to dashboard') }}</a>
            @elseif(auth()->user()->currentTeam)
                <a href="{{ route('aura.dashboard') }}" class="block mt-0.5 text-xs truncate aura-sidebar-team-name hover:underline">&larr; {{ __('Back to :team', ['team' => auth()->user()->currentTeam->name]) }}</a>
            @endif
        </div>
    </div>
@else
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
        <div class="px-2 {{ ($this->compact ? 'w-28' : 'w-48') }}">
            <x-aura::application-logo class="w-full h-8 text-gray-700 fill-current" />
        </div>
    @else
        <div class="px-2 {{ ($this->compact ? 'w-28' : 'w-48') }}">
            <x-aura::application-logo class="w-full h-8 text-white fill-current dark:text-white" />
        </div>
    @endif
@endif

@endif
