@if ($settings)
   @if ($sidebarType == 'primary')
        @if (isset($settings['app-logo-darkmode']))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo-darkmode'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @elseif (isset($settings['app-logo']))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo'][0]) ) }}" alt="{{ optional($settings)['title'] }}" class="h-6">
        @endif
    @elseif ($sidebarType == 'light')
        @if (optional($settings['app-logo-darkmode'])[0] && optional($settings['app-logo'])[0])
            <img class="hidden dark:block" src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo-darkmode'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
            <img class="block dark:hidden" src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo-darkmode'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @elseif (optional($settings['app-logo']))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @endif
    @elseif ($sidebarType == 'dark')
        @if (isset($settings['app-logo-darkmode']))
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo-darkmode'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @elseif (optional($settings['app-logo'])[0])
            <img src="{{ asset('storage/' . app('aura')::getPath($settings['app-logo'][0]) ) }}" alt="{{ $settings['title'] ?? '' }}" class="h-6">
        @endif
    @endif

    @if (!isset($settings['app-logo']))
        <svg class="dark:hidden" width="123" height="24" viewBox="0 0 123 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M44.5089 24C50.1237 24 54.1979 19.6519 54.1979 14.174V0H48.6173V14.174C48.6173 16.3994 47.0767 18.2825 44.5089 18.2825C41.9069 18.2825 40.4005 16.3994 40.4005 14.174V0H34.7857V14.174C34.7857 19.6519 38.8941 24 44.5089 24Z" fill="currentColor"/>
            <path d="M72.6897 23.8117H66.9379V0.188278H77.654C83.6112 0.188278 86.5556 4.53635 86.5556 8.81595C86.5556 12.1027 84.9464 15.1498 81.8651 16.5192L86.9664 23.8117H80.2902L75.8052 17.3752V12.4108H77.2774C79.2631 12.4108 80.9065 10.8017 80.9065 8.81595C80.9065 6.69327 79.2631 5.18685 77.2774 5.18685H72.6897V23.8117Z" fill="currentColor"/>
            <path d="M122.214 23.7945H115.914L111.327 15.7146L108.827 11.1611L106.328 15.7146L103.418 20.7817L101.74 23.8287L95.4406 23.7945L108.827 0.17111L122.214 23.7945Z" fill="currentColor"/>
            <path d="M26.7732 23.7945H20.4736L15.8859 15.7146L13.3866 11.1611L10.8873 15.7146L7.97717 20.7817L6.29957 23.8287L0 23.7945L13.3866 0.17111L26.7732 23.7945Z" fill="currentColor"/>
        </svg>
    @endif

    @if (!isset($settings['app-darklogo']))
        <svg class="hidden dark:block" width="123" height="24" viewBox="0 0 123 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M44.5089 24C50.1237 24 54.1979 19.6519 54.1979 14.174V0H48.6173V14.174C48.6173 16.3994 47.0767 18.2825 44.5089 18.2825C41.9069 18.2825 40.4005 16.3994 40.4005 14.174V0H34.7857V14.174C34.7857 19.6519 38.8941 24 44.5089 24Z" fill="currentColor"/>
            <path d="M72.6897 23.8117H66.9379V0.188278H77.654C83.6112 0.188278 86.5556 4.53635 86.5556 8.81595C86.5556 12.1027 84.9464 15.1498 81.8651 16.5192L86.9664 23.8117H80.2902L75.8052 17.3752V12.4108H77.2774C79.2631 12.4108 80.9065 10.8017 80.9065 8.81595C80.9065 6.69327 79.2631 5.18685 77.2774 5.18685H72.6897V23.8117Z" fill="currentColor"/>
            <path d="M122.214 23.7945H115.914L111.327 15.7146L108.827 11.1611L106.328 15.7146L103.418 20.7817L101.74 23.8287L95.4406 23.7945L108.827 0.17111L122.214 23.7945Z" fill="currentColor"/>
            <path d="M26.7732 23.7945H20.4736L15.8859 15.7146L13.3866 11.1611L10.8873 15.7146L7.97717 20.7817L6.29957 23.8287L0 23.7945L13.3866 0.17111L26.7732 23.7945Z" fill="currentColor"/>
        </svg>
    @endif

@else
    <svg width="123" height="24" viewBox="0 0 123 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M44.5089 24C50.1237 24 54.1979 19.6519 54.1979 14.174V0H48.6173V14.174C48.6173 16.3994 47.0767 18.2825 44.5089 18.2825C41.9069 18.2825 40.4005 16.3994 40.4005 14.174V0H34.7857V14.174C34.7857 19.6519 38.8941 24 44.5089 24Z" fill="currentColor"/>
        <path d="M72.6897 23.8117H66.9379V0.188278H77.654C83.6112 0.188278 86.5556 4.53635 86.5556 8.81595C86.5556 12.1027 84.9464 15.1498 81.8651 16.5192L86.9664 23.8117H80.2902L75.8052 17.3752V12.4108H77.2774C79.2631 12.4108 80.9065 10.8017 80.9065 8.81595C80.9065 6.69327 79.2631 5.18685 77.2774 5.18685H72.6897V23.8117Z" fill="currentColor"/>
        <path d="M122.214 23.7945H115.914L111.327 15.7146L108.827 11.1611L106.328 15.7146L103.418 20.7817L101.74 23.8287L95.4406 23.7945L108.827 0.17111L122.214 23.7945Z" fill="currentColor"/>
        <path d="M26.7732 23.7945H20.4736L15.8859 15.7146L13.3866 11.1611L10.8873 15.7146L7.97717 20.7817L6.29957 23.8287L0 23.7945L13.3866 0.17111L26.7732 23.7945Z" fill="currentColor"/>
    </svg>
@endif
