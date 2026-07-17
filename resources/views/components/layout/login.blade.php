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
<body class="font-sans antialiased text-gray-800 bg-gray-50 dark:bg-gray-950 dark:text-gray-100">

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
        @unless ($hasCustomBg)
            <div aria-hidden="true" class="overflow-hidden absolute inset-0 -z-10 pointer-events-none">
                <div class="absolute inset-x-0 top-0 h-96 bg-gradient-to-b to-transparent from-white dark:from-white/[0.04]"></div>
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[64rem] h-[32rem] rounded-full opacity-60 blur-3xl bg-gradient-to-b from-primary-100/60 to-transparent dark:from-primary-500/[0.07] dark:opacity-100"></div>
            </div>
        @endunless

        <main class="flex relative flex-col justify-center items-center px-6 py-12 min-h-screen">
            <div class="w-full sm:max-w-md">
                <div class="flex justify-center">
                    <a href="/" class="block w-32 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <x-dynamic-component :component="config('aura.views.logo')" />
                    </a>
                </div>

                <div @class([
                    'mt-8 w-full px-6 py-8 sm:px-10 sm:py-10 rounded-2xl ring-1',
                    'bg-white ring-gray-950/10 shadow-xl shadow-gray-950/[0.04] dark:bg-gray-900 dark:ring-white/10 dark:shadow-black/30' => ! $hasCustomBg,
                    'backdrop-blur-xl bg-white/95 ring-gray-950/10 shadow-xl shadow-gray-950/[0.08] dark:bg-gray-900/90 dark:ring-white/10 dark:shadow-black/40' => $hasCustomBg,
                ])>
                    {{ $slot }}
                </div>

                <p class="mt-8 text-xs text-center text-gray-400 dark:text-gray-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}
                </p>
            </div>
        </main>
    </div>
</body>
</html>
