<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Aura CMS') }}</title>

    @auraStyles

    @include('aura::components.layout.favicon')

    @php
        $loginBgUrl = $appSettings['theme']['login-bg'] ?? null;
        $loginBgDarkUrl = $appSettings['theme']['login-bg-darkmode'] ?? null;
        $hasCustomBg = $loginBgUrl || $loginBgDarkUrl;
    @endphp

</head>
<body class="font-sans antialiased text-gray-800 bg-gray-50 dark:bg-gray-900 dark:text-gray-100">

    @if ($loginBgUrl && $loginBgDarkUrl)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const images = document.querySelectorAll('[data-darkmode-image]')

                images.forEach(image => {
                    if (document.documentElement.classList.contains('dark')) {
                        image.style.backgroundImage = `url(${image.dataset.darkmodeImage})`
                    }
                })
            })
        </script>
    @endif

    <div class="isolate relative bg-bottom bg-no-repeat bg-cover"
        @if ($loginBgUrl && $loginBgDarkUrl)
            style="background-image: url('{{ $loginBgUrl }}');"
            data-darkmode-image="{{ $loginBgDarkUrl }}"
        @elseif ($loginBgUrl)
            style="background-image: url('{{ $loginBgUrl }}');"
        @elseif ($loginBgDarkUrl)
            style="background-image: url('{{ $loginBgDarkUrl }}');"
        @endif
    >
        <main class="flex relative flex-col justify-center items-center px-6 py-12 min-h-screen">
            <div class="w-full sm:max-w-sm">
                <div class="flex justify-center">
                    <a href="/" class="block w-32 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <x-dynamic-component :component="config('aura.views.logo')" />
                    </a>
                </div>

                <div @class([
                    'mt-10 w-full',
                    'p-8 ring-1 shadow-xl backdrop-blur-xl bg-white/95 rounded-2xl ring-gray-950/5 shadow-gray-950/[0.08] dark:bg-gray-900/90 dark:ring-white/10 dark:shadow-black/40' => $hasCustomBg,
                ])>
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>
