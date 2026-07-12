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
    @endphp

</head>
<body class="font-sans antialiased text-gray-800 bg-gray-50 dark:bg-gray-900 dark:text-gray-100">

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

    <div class="isolate overflow-hidden relative bg-gray-50 bg-bottom bg-no-repeat bg-cover dark:bg-gray-900"
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
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                {{-- Soft radial glow behind the card --}}
                <div class="absolute top-1/2 left-1/2 w-[42rem] h-[42rem] rounded-full -translate-x-1/2 -translate-y-1/2 bg-primary-500/[0.06] blur-3xl dark:bg-primary-500/[0.07]"></div>

                {{-- Triangle grid, faded out towards the edges --}}
                <div class="absolute inset-0" style="mask-image: radial-gradient(ellipse 80% 70% at 50% 45%, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0) 80%); -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 45%, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0) 80%);">
                    <svg aria-hidden="true"
                        class="absolute inset-x-0 inset-y-0 w-full h-full text-gray-950/[0.07] dark:text-white/[0.06]"
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

        <main class="flex relative flex-col justify-center items-center px-6 py-12 min-h-screen">
            <div class="w-full sm:max-w-md">
                <div class="flex justify-center">
                    <a href="/" class="block w-2/3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <x-dynamic-component :component="config('aura.views.logo')" />
                    </a>
                </div>

                <div class="p-8 mt-8 w-full ring-1 shadow-xl backdrop-blur-xl bg-white/95 rounded-2xl ring-gray-950/5 shadow-gray-950/[0.08] dark:bg-gray-800/90 dark:ring-white/10 dark:shadow-black/40 sm:p-10">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>
