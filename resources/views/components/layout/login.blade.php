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
                'darkmode-type' => Aura::option('darkmode-type'),
                'color-palette' => Aura::option('color-palette'),
                'gray-color-palette' => Aura::option('gray-color-palette'),
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

        <div class="relative overflow-hidden bg-gray-100 bg-bottom bg-no-repeat bg-cover group dark:bg-gray-900 isolate"
            @if ($image = Attachment::find(Aura::option('login-bg'))->first()->path())
            {{-- style="background-image: url('{{ $image }}');" --}}
            @else
            {{-- style="background-image: url('/vendor/aura/assets/img/bgop1.jpg');" --}}
            @endif
        >

        {{-- <div class="pointer-events-none">
            <div class="absolute inset-0 rounded-2xl transition duration-300 [mask-image:linear-gradient(135deg,white,transparent)] group-hover:opacity-50">
                <svg aria-hidden="true" class="absolute inset-x-0 inset-y-[-30%] h-[160%] w-full skew-y-[-18deg] fill-red-500/[0.5] stroke-red-500/95 dark:fill-white/1 dark:stroke-white/2.5">
                    <defs>
                        <pattern id=":R56hd6:" width="72" height="56" patternUnits="userSpaceOnUse" x="50%" y="16"><path d="M.5 56V.5H72" fill="none"></path></pattern>
                    </defs>

                    <rect width="100%" height="100%" stroke-width="0" fill="url(#:R56hd6:)"></rect>

                    <svg x="50%" y="16" class="overflow-visible">
                        <rect stroke-width="0" width="73" height="57" x="0" y="56"></rect>
                        <rect stroke-width="0" width="73" height="57" x="72" y="168"></rect>
                    </svg>
                </svg>
            </div>
        </div> --}}

        {{-- <div class="pointer-events-none">
            <div class="absolute inset-0 rounded-2xl transition duration-300 [mask-image:linear-gradient(135deg,white,transparent)] group-hover:opacity-50">
                <svg aria-hidden="true" class="absolute inset-x-0 inset-y-0 h-full w-full stroke-gray-500/95 dark:stroke-white/2.5" fill="none">
                    <defs>
                        <pattern id="trianglePattern" width="30" height="26" patternUnits="userSpaceOnUse" patternTransform="translate(0, -1)">
                            <g>
                                <path d="M 15 0 L 30 25 L 0 25 Z"></path>
                                <use href="#tri" x="15" y="13"></use>
                            </g>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#trianglePattern)"></rect>
                </svg>
            </div>
        </div> --}}

        {{-- <div class="pointer-events-none">
            <div class="absolute inset-0 rounded-2xl transition duration-300 [mask-image:linear-gradient(135deg,white,transparent)] group-hover:opacity-50">
                <svg aria-hidden="true" class="absolute inset-x-0 inset-y-0 h-full w-full stroke-gray-500/95 dark:stroke-white/2.5" fill="none" stroke-width="1">
                    <defs>
                        <pattern id="trianglePattern" width="30" height="26" patternUnits="userSpaceOnUse" patternTransform="translate(15, -1)">
                            <g>
                                <path d="M 15 0 L 30 25 L 0 25 Z" stroke="currentColor"></path>
                                <use href="#tri" x="15" y="13"></use>
                            </g>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#trianglePattern)"></rect>
                </svg>
            </div>
        </div> --}}


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





      <div class="absolute inset-0 bg-transparent dark:bg-transparent -z-10"></div>

        <div class="relative flex flex-col items-center min-h-screen pt-6 bg-bottom bg-no-repeat bg-cover sm:justify-center sm:pt-0">
            <div>
                <a href="/">
                    <x-aura::application-logo class="h-10 text-gray-600 fill-current dark:text-gray-100" />
                </a>
            </div>

            <div class="w-full px-6 py-4 pb-6 mt-6 overflow-hidden border border-gray-300 shadow-md dark:border-gray-700 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm sm:max-w-md sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </div>
    </body>
</html>
