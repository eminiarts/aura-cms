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
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js'], 'vendor/aura') --}}

    {{-- @vite(['resources/css/app.css', 'resources/js/app.js', 'vendor/aura']) --}}


    @include('aura::components.layout.favicon')

    @auraStyles

    @php
        $loginBgUrl = $appSettings['login-bg'] ?? null;
        $loginBgDarkUrl = $appSettings['login-bg-darkmode'] ?? null;
    @endphp

</head>
<body class="font-sans antialiased text-gray-800 bg-white dark:bg-black dark:text-gray-100">

    @if ($loginBgUrl && $loginBgDarkUrl)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
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

    <div class="isolate overflow-hidden relative bg-gray-100 bg-bottom bg-no-repeat bg-cover group dark:bg-gray-900"
        @if ($loginBgUrl && $loginBgDarkUrl)
            style="background-image: url('{{ $loginBgUrl }}');"
            data-darkmode-image="{{ $loginBgDarkUrl }}"
        @elseif ($loginBgUrl)
            style="background-image: url('{{ $loginBgUrl }}');"
        @elseif ($loginBgDarkUrl)
            style="background-image: url('{{ $loginBgDarkUrl }}');"
        @endif
    >
        @if (!$loginBgUrl && !$loginBgDarkUrl)
            <div class="pointer-events-none">
                <div class="absolute inset-0 transition duration-300" style="mask-image: radial-gradient(circle, rgba(255,255,255,0.8) 10%, rgba(255,255,255,0) 85%); transform: opacity-90 group-hover:opacity-100;">
                    <svg aria-hidden="true"
                        class="absolute inset-x-0 inset-y-0 w-full h-full text-gray-200 dark:text-gray-700/70"
                        fill="none" stroke-width="1">
                        <defs>
                            <pattern id="trianglePatternEven" viewBox="0 0 30 52" width="60" height="104" patternUnits="userSpaceOnUse"
                                    patternTransform="translate(0, -2)">
                                <g>
                                    <path d="M 15 1 L 30 26 L 0 26 Z" stroke="currentColor"></path>
                                    <use href="#tri" x="15" y="13"></use>
                                </g>
                            </pattern>
                            <pattern id="trianglePatternOdd" viewBox="0 0 30 52" width="60" height="104" patternUnits="userSpaceOnUse"
                                    patternTransform="translate(30, 0)">
                                <g>
                                    <path d="M 15 26 L 30 51 L 0 51 Z" stroke="currentColor"></path>
                                    <use href="#tri" x="15" y="39"></use>
                                </g>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#trianglePatternEven)"></rect>
                        <rect y="50" width="100%" height="100%" fill="url(#trianglePatternOdd)"></rect>
                    </svg>
                </div>
            </div>
        @endif

        <div class="absolute inset-0 bg-transparent dark:bg-transparent -z-10"></div>

        <div class="flex relative flex-col items-center pt-6 min-h-screen bg-bottom bg-no-repeat bg-cover sm:justify-center sm:pt-0">
            <div class="flex justify-center px-6 w-full sm:max-w-md">
                <div class="w-2/3">
                    <a href="/">
                        <x-dynamic-component :component="config('aura.views.logo')" />
                    </a>
                </div>
            </div>
            <div class="overflow-hidden px-6 py-4 pb-6 mt-6 w-full border border-gray-300 shadow-md backdrop-blur-sm dark:border-gray-700 bg-white/50 dark:bg-gray-800/80 sm:max-w-md sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
