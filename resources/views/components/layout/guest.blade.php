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
    <body class="font-sans antialiased text-gray-800">

        {{ $slot }}

    </body>
</html>
