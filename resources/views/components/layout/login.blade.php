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

        <div class="relative overflow-hidden bg-white bg-bottom bg-no-repeat bg-cover dark:bg-black isolate"
            @if ($image = Attachment::find(Aura::option('login-bg'))->first()->path())
            style="background-image: url('{{ $image }}');"
            @else
            style="background-image: url('/vendor/aura/assets/img/bgop1.jpg');"
            @endif
        >

      <div class="absolute inset-0 bg-white/40 dark:bg-black/40 -z-10"></div>

        <div class="flex flex-col items-center min-h-screen pt-6 bg-bottom bg-no-repeat bg-cover sm:justify-center sm:pt-0">
            <div>
                <a href="/">
                    <x-aura::application-logo class="h-10 text-gray-100 fill-current dark:text-gray-100" />
                </a>
            </div>

            <div class="w-full px-6 py-4 pb-6 mt-6 overflow-hidden text-white border border-gray-300 shadow-md dark:border-gray-700 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm sm:max-w-md sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </div>
    </body>
</html>
