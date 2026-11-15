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

    @if ($hasCustomBg)
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
                <div class="w-full sm:max-w-md">
                    <div class="flex justify-center">
                        <a href="/" class="block w-32 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                            <x-dynamic-component :component="config('aura.views.logo')" />
                        </a>
                    </div>

                    <div class="aura-auth mt-8 w-full px-6 py-8 sm:px-10 sm:py-10 rounded-2xl ring-1 backdrop-blur-xl bg-white/95 ring-gray-950/10 shadow-xl shadow-gray-950/[0.08] dark:bg-gray-900/90 dark:ring-white/10 dark:shadow-black/40">
                        {{ $slot }}
                    </div>

                    <p class="mt-8 text-xs text-center text-gray-400 dark:text-gray-500">
                        &copy; {{ date('Y') }} {{ config('app.name') }}
                    </p>
                </div>
            </main>
        </div>
    @else
        @php
            // From lg the page is split: a brand panel in the sidebar's own colors (sidebar type and its
            // dark-mode type from Settings) and the form beside it, no card. Below lg: wordmark, then form.
            $themeSettings = app('aura')::getOption('settings') ?? [];
            $sidebarType = $themeSettings['sidebar-type'] ?? config('aura.theme.sidebar-type', 'dark');
            $sidebarDarkType = $themeSettings['sidebar-darkmode-type'] ?? config('aura.theme.sidebar-darkmode-type', 'dark');
            $panelOnLight = $sidebarType === 'light';
            $panelOnLightDark = $sidebarDarkType === 'light';
            // The Λ outline repeats Aura's own wordmark: with a custom logo it would be a stranger's mark.
            $customLogo = config('aura.views.logo') !== 'aura::application-logo'
                || filled($themeSettings['logo'] ?? null)
                || filled($themeSettings['logo-darkmode'] ?? null);
            // aura.theme.login-line: null shows the default line, a string replaces it, false hides it.
            $loginLine = config('aura.theme.login-line');
            $loginLine = $loginLine === false ? null : __(is_string($loginLine) && $loginLine !== '' ? $loginLine : 'Every record in one place.');
            // The mark before the line takes the active sidebar item's mark color.
            $markColor = ['light' => 'bg-primary-600', 'primary' => 'bg-white', 'dark' => 'bg-primary-400'][$sidebarType] ?? 'bg-primary-400';
            $markDarkColor = ['light' => 'dark:bg-primary-600', 'primary' => 'dark:bg-white', 'dark' => 'dark:bg-primary-400'][$sidebarDarkType] ?? 'dark:bg-primary-400';
            // aura.theme.login-image: one image, or one per color scheme.
            $loginImage = config('aura.theme.login-image');
            $loginImage = is_array($loginImage) ? $loginImage : ['light' => $loginImage, 'dark' => $loginImage];
            $loginImageUrl = fn ($path) => filled($path) ? (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset($path)) : null;
            $loginImageLight = $loginImageUrl($loginImage['light'] ?? null) ?? $loginImageUrl($loginImage['dark'] ?? null);
            $loginImageDark = $loginImageUrl($loginImage['dark'] ?? null) ?? $loginImageLight;
        @endphp

        {{-- The form pane is the app's page: white, dark gray-900, so inputs and buttons look as they do in the app.
             In dark mode the dark brand panel is the dark sidebar (gray-950), one step below the form pane. --}}
        <div class="min-h-screen bg-white lg:grid lg:grid-cols-12 dark:bg-gray-900">
            <aside class="hidden lg:block lg:col-span-5 aura-sidebar-type-{{ $sidebarType }} aura-sidebar-darkmode-type-{{ $sidebarDarkType }}">
                <div class="flex overflow-hidden relative flex-col justify-between p-10 h-full aura-sidebar-bg">
                    @php
                        $wordmarkColor = ($panelOnLight ? 'text-gray-900' : 'text-white').' '.($panelOnLightDark ? 'dark:text-gray-900' : 'dark:text-white');
                    @endphp
                    <a href="/" class="relative block w-[7.5rem] rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <x-dynamic-component :component="config('aura.views.logo')" class="block w-full h-auto {{ $wordmarkColor }}" />
                    </a>

                    @if ($loginImageLight)
                        {{-- The product: 720px wide from the panel's left padding, below the login line, bleeding off
                             the right and bottom edge. The dark-scheme image swaps in with the dark class. --}}
                        <img src="{{ $loginImageLight }}" alt="" aria-hidden="true" data-login-image
                            @class(['absolute left-10 top-[44%] w-[720px] max-w-none rounded-tl-xl ring-1 shadow-2xl ring-white/10 shadow-black/40 pointer-events-none select-none', 'dark:hidden' => $loginImageDark !== $loginImageLight])>
                        @if ($loginImageDark !== $loginImageLight)
                            <img src="{{ $loginImageDark }}" alt="" aria-hidden="true" data-login-image-dark
                                class="hidden absolute left-10 top-[44%] w-[720px] max-w-none rounded-tl-xl ring-1 shadow-2xl ring-white/10 shadow-black/40 pointer-events-none select-none dark:block">
                        @endif
                    @elseif (! $customLogo)
                        {{-- One Λ of the wordmark, outlined at 1px and cropped by the panel's corner. --}}
                        <x-aura::icon.apex-outline :class="\Illuminate\Support\Arr::toCssClasses([
                            'absolute -right-32 -bottom-24 w-[36rem] pointer-events-none',
                            $panelOnLight ? 'stroke-gray-950/10' : 'stroke-white/10',
                            $panelOnLightDark ? 'dark:stroke-gray-950/10' : 'dark:stroke-white/10',
                        ])" />
                    @endif

                    {{-- With a product image the line moves up to the top third, above the image. --}}
                    <div @class(['relative', 'mt-[12vh] mb-auto' => $loginImageLight])>
                        @if ($loginLine)
                            <div class="flex gap-3 items-start mb-6" data-login-line>
                                <span class="mt-1 w-[3px] h-6 shrink-0 {{ $markColor }} {{ $markDarkColor }}" aria-hidden="true"></span>
                                <p @class([
                                    'max-w-xs text-2xl leading-8',
                                    $panelOnLight ? 'text-gray-900' : 'text-white',
                                    $panelOnLightDark ? 'dark:text-gray-900' : 'dark:text-white',
                                ]) style="font-weight: 650; letter-spacing: -0.022em">{{ $loginLine }}</p>
                            </div>
                        @endif

                        <p @class([
                            'text-xs',
                            $panelOnLight ? 'text-gray-500' : 'text-white/60',
                            $panelOnLightDark ? 'dark:text-gray-500' : 'dark:text-white/60',
                        ])>
                            &copy; {{ date('Y') }} {{ config('app.name') }}
                        </p>
                    </div>
                </div>
            </aside>

            <main class="flex flex-col px-6 py-6 min-h-screen sm:px-10 sm:py-8 lg:col-span-7">
                <div class="lg:hidden">
                    <a href="/" class="block w-[7.5rem] rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <x-dynamic-component :component="config('aura.views.logo')" class="block w-full h-auto text-gray-900 dark:text-white" />
                    </a>
                </div>

                <div class="flex flex-1 justify-center items-start pt-14 pb-12 lg:items-center lg:py-12">
                    <div class="w-full max-w-sm aura-auth">
                        {{ $slot }}
                    </div>
                </div>

                <p class="text-xs text-gray-400 lg:hidden dark:text-gray-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}
                </p>
            </main>
        </div>
    @endif
</body>
</html>
