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

        <title>@yield('title') • Aura CMS</title>

        @php
            $settings = Eminiarts\Aura\Facades\Aura::getOption('team-settings');

            $appSettings = Eminiarts\Aura\Facades\Aura::options();
        @endphp

        @php
            use Eminiarts\Aura\Resources\Attachment;


            $favicon = $darkFavicon = '/vendor/aura/public/favicon-32x32.png';

            if(isset($appSettings['app-favicon'])) {
                $appFavicon = $appSettings['app-favicon'];
                $favicon = optional(Attachment::find($appFavicon)->first())->path();
            }

            if(isset($appSettings['app-favicon-darkmode'])) {
                $appFaviconDark = $appSettings['app-favicon-darkmode'];
                $darkFavicon = optional(Attachment::find($appFaviconDark)->first())->path();
            }

            if (!$favicon) {
                $favicon = $darkFavicon;
            }

            if (!$darkFavicon) {
                $darkFavicon = $favicon;
            }

        @endphp

        <link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon }}">

        <style>[x-cloak] { display: none !important; }</style>
        <link rel="stylesheet" href="/vendor/aura/public/inter.css">

        @vite(['resources/css/app.css'], 'vendor/aura')

        @include('aura::components.layout.colors')

        @stack('styles')
    </head>
    <body class="overflow-hidden antialiased text-gray-800 bg-white dark:bg-gray-900 dark:text-gray-200">

        <div
            x-data="aura"
            @keydown.window.slash="$dispatch('search')"
            @keydown.window.prevent.cmd.k="$dispatch('search')"
            @keydown.window.escape="closeSearch()"
            @inset-sidebar.window="insetSidebar(event)"
            class="flex flex-col items-stretch h-screen overflow-hidden md:flex-row"
        >

            <livewire:aura::navigation />


            <div class="flex flex-col flex-grow w-screen h-screen aura-content">

                @if (! app('aura')::assetsAreCurrent())
                    <div class="p-4 text-xs text-red-800 bg-red-100">
                        The published assets are not up-to-date with the installed version. To update, run:<br/><code class="font-bold">php artisan aura:publish</code>
                    </div>
                @endif


                <div class="flex-1 overflow-y-auto">
                    <div class="p-5 md:p-8">
                        {{ $slot }}
                    </div>
                    <div class="flex justify-end px-8 pb-8">
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

        @livewire('wire-elements-modal')

        @stack('scripts')

        <script>
            if (localStorage.getItem('leftSidebar') === 'true') {
                document.querySelector('.aura-navigation-collapsed').setAttribute('x-cloak', '');
            } else {
                document.querySelector('.aura-navigation').setAttribute('x-cloak', '');
            }
        </script>

  <script>
      // after 100ms trigger a window resize event to force the chart to redraw
      setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
      }, 0);
      setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
      }, 100);

      const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

      console.log('darkModeMediaQuery', darkModeMediaQuery);

        function setFaviconBasedOnPreferredColorScheme(event) {
            if (event.matches) {
                // The user has set their browser to prefer dark mode, so show the darkmode favicon
                document.querySelector("link[sizes='32x32']").href = '{{ $darkFavicon }}';
                console.log('set dark favicon', '{{ $darkFavicon }}');
            } else {
                // The user has set their browser to prefer light mode, so show the lightmode favicon
                document.querySelector("link[sizes='32x32']").href = '{{ $favicon }}';
            }
        }

        darkModeMediaQuery.addListener(setFaviconBasedOnPreferredColorScheme);

        // Set the initial value
        setFaviconBasedOnPreferredColorScheme(darkModeMediaQuery);

  </script>

        @vite(['resources/js/app.js'], 'vendor/aura')
        @vite(['resources/js/apexcharts.js'], 'vendor/aura')

        @livewireScriptConfig
    </body>
</html>
