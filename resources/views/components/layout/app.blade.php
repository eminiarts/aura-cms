@props([
    'header' => null,
    'sidebar' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title')Aura CMS</title>

        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

        <!-- Fonts -->
        <link rel="stylesheet" href="/css/inter.css">

        @livewireStyles

        @vite(['resources/css/app.css'], 'vendor/aura')

        @php
            $settings = Eminiarts\Aura\Aura::getOption('team-settings');
        @endphp

        @include('aura::components.layout.colors')


        {{-- <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet" /> --}}

        @stack('styles')

        <script>
            function getCssVariableValue(variableName) {
                var rgb = getComputedStyle(document.documentElement).getPropertyValue(variableName);

                // split string by " "
                rgb = rgb.split(" ");

                return rgbToHex(rgb[0], rgb[1], rgb[2]);
            }

            function rgbToHex(r, g, b) {
                return "#" + (1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1);
            }

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

            const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            function setFaviconBasedOnPreferredColorScheme(event) {
                if (event.matches) {
                    // The user has set their browser to prefer dark mode, so show the darkmode favicon
                    document.querySelector("link[sizes='32x32']").href = '/favicon-darkmode-32x32.png';
                    document.querySelector("link[sizes='16x16']").href = '/favicon-darkmode-16x16.png';
                } else {
                    // The user has set their browser to prefer light mode, so show the lightmode favicon
                    document.querySelector("link[sizes='32x32']").href = '/favicon-32x32.png';
                    document.querySelector("link[sizes='16x16']").href = '/favicon-16x16.png';
                }
            }

            darkModeMediaQuery.addListener(setFaviconBasedOnPreferredColorScheme);

            // Set the initial value
            setFaviconBasedOnPreferredColorScheme(darkModeMediaQuery);
        </script>
    </head>
    <body class="overflow-hidden antialiased text-gray-800 bg-white dark:bg-gray-900 dark:text-gray-200">

        <div
            x-aura::data="aura"
            @keydown.window.slash="$dispatch('search')"
            @keydown.window.prevent.cmd.k="$dispatch('search')"
            @keydown.window.escape="closeSearch()"
            @inset-sidebar.window="insetSidebar(event)"
            class="flex flex-col items-stretch h-screen overflow-hidden md:flex-row"
        >

            <x-aura::navigation />


            <div class="flex flex-col flex-grow w-screen h-screen aura-content">


                <div class="flex-1 overflow-y-auto">
                    <div class="p-5 md:p-8">
                        {{ $slot }}
                    </div>
                    <div class="flex justify-end px-aura::8 pb-8">
                        <div class="flex flex-col text-gray-200 dark:text-gray-700">
                                <a href="https://auracms.com/" target="_blank">
                                    <svg width="123" height="24" viewBox="0 0 123 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M44.5089 24C50.1237 24 54.1979 19.6519 54.1979 14.174V0H48.6173V14.174C48.6173 16.3994 47.0767 18.2825 44.5089 18.2825C41.9069 18.2825 40.4005 16.3994 40.4005 14.174V0H34.7857V14.174C34.7857 19.6519 38.8941 24 44.5089 24Z" fill="currentColor"/>
                                        <path d="M72.6897 23.8117H66.9379V0.188278H77.654C83.6112 0.188278 86.5556 4.53635 86.5556 8.81595C86.5556 12.1027 84.9464 15.1498 81.8651 16.5192L86.9664 23.8117H80.2902L75.8052 17.3752V12.4108H77.2774C79.2631 12.4108 80.9065 10.8017 80.9065 8.81595C80.9065 6.69327 79.2631 5.18685 77.2774 5.18685H72.6897V23.8117Z" fill="currentColor"/>
                                        <path d="M122.214 23.7945H115.914L111.327 15.7146L108.827 11.1611L106.328 15.7146L103.418 20.7817L101.74 23.8287L95.4406 23.7945L108.827 0.17111L122.214 23.7945Z" fill="currentColor"/>
                                        <path d="M26.7732 23.7945H20.4736L15.8859 15.7146L13.3866 11.1611L10.8873 15.7146L7.97717 20.7817L6.29957 23.8287L0 23.7945L13.3866 0.17111L26.7732 23.7945Z" fill="currentColor"/>
                                    </svg>
                                </a>

                                <span class="text-sm text-gray-300 dark:text-gray-600">v0.0.1</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($sidebar)
            <x-aura::sidebar>
                {{ $sidebar  }}
            </x-aura::sidebar>
            @endif

            @yield('sidebar')

            <livewire:aura::global-search />
        </div>

        @stack('modals')

        <x-aura::notification />

        <livewire:aura::notifications />

        @livewireScripts

        @livewire('livewire-ui-modal')

        @stack('scripts')

        <script src="https://unpkg.com/@yaireo/tagify"></script>
        <script src="https://unpkg.com/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
        <link href="https://unpkg.com/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />

        <!-- Alpine Plugins -->
        <script defer src="https://unpkg.com/@alpinejs/collapse@3.10.5/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/@alpinejs/ui@3.10.5-beta.8/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/@alpinejs/focus@3.10.5/dist/cdn.min.js"></script>

        @vite(['resources/js/app.js'], 'vendor/aura')

    </body>
</html>
