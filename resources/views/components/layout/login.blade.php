<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Aura CMS') }}</title>

        <!-- Scripts -->
        {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
        {{-- <link rel="stylesheet" href="/css/app.css"> --}}
        {{-- <script defer src="/js/app.js"></script> --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'], 'vendor/aura')

        @php

            $settings = [
                'darkmode-type' => app('aura')::option('darkmode-type'),
                'color-palette' => app('aura')::option('color-palette'),
                'gray-color-palette' => app('aura')::option('gray-color-palette'),
            ];
        @endphp

        @include('aura::components.layout.colors')

        <script>
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
        </script>

    </head>
    <body class="font-sans antialiased text-gray-800 bg-white dark:bg-black dark:text-gray-100">

        @php
        use Eminiarts\Aura\Resources\Attachment;
        @endphp

        @if (
            ($image = Attachment::find(app('aura')::option('login-bg'))) &&
            $image->isNotEmpty() &&
            ($imageDark = Attachment::find(app('aura')::option('login-bg-darkmode'))) &&
            $imageDark->isNotEmpty()
        )
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const images = document.querySelectorAll('[data-darkmode-image]')
                const darkmode = window.matchMedia('(prefers-color-scheme: dark)').matches

                images.forEach(image => {
                    if (document.documentElement.classList.contains('dark')) {
                        image.style.backgroundImage = `url(${image.dataset.darkmodeImage})`
                    }
                })
            })
        </script>
        @endif

        <div class="relative overflow-hidden bg-gray-100 bg-bottom bg-no-repeat bg-cover group dark:bg-gray-900 isolate"

            {{-- @if ($image = Attachment::find(app('aura')::option('login-bg')))
            style="background-image: url('{{ $image->first()->path() }}');"
            @else
            {{-- style="background-image: url('/vendor/aura/public/img/bgop1.jpg');"
            @endif --}}

            @if (
                ($image = Attachment::find(app('aura')::option('login-bg'))) &&
                $image->isNotEmpty() &&
                ($imageDark = Attachment::find(app('aura')::option('login-bg-darkmode'))) &&
                $imageDark->isNotEmpty()
            )
                style="background-image: url('{{ $image->first()->path() }}');"
                data-darkmode-image="{{ $imageDark->first()->path() }}"
            @elseif (
                ($image = Attachment::find(app('aura')::option('login-bg'))) &&
                $image->isNotEmpty()
            )
                style="background-image: url('{{ $image->first()->path() }}');"
            @elseif (
                ($imageDark = Attachment::find(app('aura')::option('login-bg-darkmode'))) &&
                $imageDark->isNotEmpty()
            )
                style="background-image: url('{{ $imageDark->first()->path() }}');"
            @else
            @endif
        >
        @if (!$image || !$image->isNotEmpty() || !$imageDark || !$imageDark->isNotEmpty())
        <div class="pointer-events-none">
            <div class="absolute inset-0 transition duration-300 [mask-image:linear-gradient(180deg,rgba(255,255,255,0.8),rgba(255,255,255,0.3))] transform opacity-90 group-hover:opacity-100">
                <svg aria-hidden="true" class="absolute inset-x-0 inset-y-0 w-full h-full text-gray-200/70 dark:text-gray-700/70" fill="none" stroke-width="1">
                    <defs>
                        <pattern id="trianglePatternEven" width="30" height="52" patternUnits="userSpaceOnUse" patternTransform="translate(0, -1)">
                            <g>
                                <path d="M 15 1 L 30 26 L 0 26 Z" stroke="currentColor"></path>
                                <use href="#tri" x="15" y="13"></use>
                            </g>
                        </pattern>
                        <pattern id="trianglePatternOdd" width="30" height="52" patternUnits="userSpaceOnUse" patternTransform="translate(15, 0)">
                            <g>
                                <path d="M 15 26 L 30 51 L 0 51 Z" stroke="currentColor"></path>
                                <use href="#tri" x="15" y="39"></use>
                            </g>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#trianglePatternEven)"></rect>
                    <rect y="26" width="100%" height="100%" fill="url(#trianglePatternOdd)"></rect>
                </svg>
            </div>
        </div>
        @endif

      <div class="absolute inset-0 bg-transparent dark:bg-transparent -z-10"></div>

        <div class="relative flex flex-col items-center min-h-screen pt-6 bg-bottom bg-no-repeat bg-cover sm:justify-center sm:pt-0">
            <div class="flex justify-center w-full px-6 sm:max-w-md">
                <div class="w-2/3">
                    <a href="/">
                        <x-aura::application-logo class="w-full text-gray-600 fill-current dark:text-gray-100" />
                    </a>
                </div>
            </div>

            <div class="w-full px-6 py-4 pb-6 mt-6 overflow-hidden border border-gray-300 shadow-md dark:border-gray-700 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm sm:max-w-md sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </div>
    </body>
</html>
