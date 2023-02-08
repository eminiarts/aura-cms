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

    </head>
    <body class="font-sans antialiased text-gray-900 bg-black dark">
        <div class="relative overflow-hidden bg-black bg-[url('/img/landing-page/bgop1.png')] bg-cover bg-no-repeat bg-opacity-50 bg-bottom isolate">
      <div class="absolute inset-0 bg-black/40 -z-10"></div>

        <div class="flex flex-col items-center min-h-screen pt-6 bg-bottom bg-no-repeat bg-cover sm:justify-center sm:pt-0">
            <div>
                <a href="/">
                    <x-aura::application-logo class="h-10 fill-current text-slate-100 dark:text-gray-100" />
                </a>
            </div>

            <div class="w-full px-6 py-4 pb-6 mt-6 overflow-hidden text-white border shadow-md border-slate-700 bg-slate-900/50 backdrop-blur-sm sm:max-w-md sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </div>
    </body>
</html>
