@php
    $shades = [25, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

    $sidebarTokens = ['--sidebar-bg', '--sidebar-bg-hover', '--sidebar-bg-dropdown', '--sidebar-icon', '--sidebar-icon-hover', '--sidebar-text'];

    $semanticColors = ['primary', 'background', 'panel', 'border', 'text', 'muted', 'success', 'warning', 'danger'];

    $statusColors = [
        'success · green-500' => 'bg-green-500',
        'error · red-500' => 'bg-red-500',
        'warning · yellow-400' => 'bg-yellow-400',
        'info · blue-500' => 'bg-blue-500',
    ];
@endphp

<div class="mt-6 space-y-8">
    @foreach (['primary' => '--primary-*', 'gray' => '--gray-*'] as $palette => $variable)
        <div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ ucfirst($palette) }} <span class="ml-2 font-normal text-gray-400">{{ $variable }}</span></h3>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(4.5rem,1fr))] gap-2 mt-3">
                @foreach ($shades as $shade)
                    <div>
                        <div class="h-12 rounded-lg ring-1 ring-inset ring-gray-950/10 dark:ring-white/10" style="background-color: rgb(var(--{{ $palette }}-{{ $shade }}))"></div>
                        <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $shade }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
    <p class="-mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Shade 950 is emitted as a variable but has no Tailwind class (primary-950, gray-950 only exist as CSS variables).') }}</p>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Sidebar tokens') }} <span class="ml-2 font-normal text-gray-400">--sidebar-*</span></h3>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(7rem,1fr))] gap-2 mt-3">
            @foreach ($sidebarTokens as $variable)
                <div>
                    <div class="h-12 rounded-lg ring-1 ring-inset ring-gray-950/10 dark:ring-white/10" style="background-color: rgb(var({{ $variable }}))"></div>
                    <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ ltrim($variable, '-') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Semantic colors') }} <span class="ml-2 font-normal text-gray-400">--aura-color-* · bg-aura-*</span></h3>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(6rem,1fr))] gap-2 mt-3">
            @foreach ($semanticColors as $name)
                <div>
                    <div class="h-12 rounded-lg ring-1 ring-inset ring-gray-950/10 dark:ring-white/10" style="background-color: rgb(var(--aura-color-{{ $name }}))"></div>
                    <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $name }}</span>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ __('Configured under theme.colors.light and theme.colors.dark. Host apps can use them; Aura\'s own components do not use them yet.') }}</p>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Status colors in components') }} <span class="ml-2 font-normal text-gray-400">{{ __('Tailwind palette') }}</span></h3>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(10rem,1fr))] gap-2 mt-3">
            @foreach ($statusColors as $label => $class)
                <div>
                    <div class="h-12 rounded-lg {{ $class }}"></div>
                    <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
