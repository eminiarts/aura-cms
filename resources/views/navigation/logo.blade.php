@if ($settings)
   @if ($sidebarType == 'primary')
        @if (isset($settings['app-logo-darkmode'][0]))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo-darkmode'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @elseif (isset($settings['app-logo'][0]))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo'][0]) ) }}" alt="{{ optional($settings)['title'] }}" class="h-6">
        @endif
    @elseif ($sidebarType == 'light')
        @if (isset($settings['app-logo-darkmode'][0]) && isset($settings['app-logo'][0]))
            <img class="hidden dark:block" src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo-darkmode'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
            <img class="block dark:hidden" src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo-darkmode'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @elseif (isset($settings['app-logo'][0]))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @endif
    @elseif ($sidebarType == 'dark')
        @if (isset($settings['app-logo-darkmode'][0]))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo-darkmode'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @elseif (isset($settings['app-logo'][0]))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @endif
    @endif

    @if (!isset($settings['app-logo'][0]) && !isset($settings['app-darklogo'][0]))
        <x-aura::application-logo class="h-10 text-gray-600 fill-current dark:text-gray-100" />
    @endif

@else
    <x-aura::application-logo class="h-10 text-gray-600 fill-current dark:text-gray-100" />
@endif
