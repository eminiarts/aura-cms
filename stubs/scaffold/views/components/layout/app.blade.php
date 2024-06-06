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

  <title>@yield('title') • {{ config('app.name', 'Aura CMS') }}</title>

  @php
      $settings = app('aura')::getOption('settings');
      $appSettings = app('aura')::options();
  @endphp
  @include('aura::components.layout.favicon')

  <style>[x-cloak] { display: none !important; }</style>

  <link rel="stylesheet" href="/vendor/aura/public/inter.css">
  @vite(['resources/css/app.css'], 'vendor/aura')
  @vite('resources/css/app.css')

  @include('aura::components.layout.colors')
  @livewireStyles
  @stack('styles')
</head>
<body class="overflow-hidden antialiased text-gray-800 bg-white dark:bg-gray-900 dark:text-gray-200">

<div
    x-data="aura"
    @keydown.window.slash="$dispatch('search')"
    @keydown.window.prevent.cmd.k="$dispatch('search')"
    @keydown.window.escape="closeSearch()"
    @inset-sidebar.window="insetSidebar(event)"
    class="flex overflow-hidden flex-col items-stretch h-screen md:flex-row"
>


    @if(auth()->check())
        <livewire:aura::navigation/>
    @else
        <x-aura::navigation.guest/>
    @endif


    <div class="flex flex-col flex-grow w-screen h-screen aura-content">

        @if (! app('aura')::assetsAreCurrent() && env('APP_ENV') === 'local')
            <div class="p-4 text-xs text-red-800 bg-red-100">
                The published assets are not up-to-date with the installed version. To update, run:<br/><code
                        class="font-bold">php artisan aura:publish</code>
            </div>
        @endif


        <div class="overflow-y-auto flex-1">
            <div class="p-5 md:p-8">
                {{ $slot }}
            </div>

            <div class="flex justify-end px-8 pb-8">
                @include('aura::components.layout.aura-version')
            </div>
        </div>
    </div>

    @if($sidebar)
        <x-aura::sidebar>
            {{ $sidebar  }}
        </x-aura::sidebar>
    @endif

    @yield('sidebar')

    <livewire:aura::global-search/>
</div>

@stack('modals')

<x-aura::notification/>

@if(config('aura.features.notifications'))
<livewire:aura::notifications/>
@endif

@livewire('wire-elements-modal')

@stack('scripts')

@vite(['resources/js/app.js'], 'vendor/aura')
{{-- @vite(['resources/js/apexcharts.js'], 'vendor/aura') --}}

@livewireScriptConfig

</body>
</html>
