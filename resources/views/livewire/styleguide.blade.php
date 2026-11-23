@php
    $sectionIds = array_keys($sections);
    $tokenStyle = collect($previewTokens)->map(fn (string $value, string $variable): string => "{$variable}: {$value}")->implode('; ');

    $previewControls = [
        'mode' => [
            'label' => __('Mode'),
            'current' => $mode,
            'options' => ['' => __('Admin'), 'light' => __('Light'), 'dark' => __('Dark')],
        ],
        'density' => [
            'label' => __('Density'),
            'current' => $density,
            'options' => ['' => __('Saved'), 'comfortable' => __('Comfortable'), 'compact' => __('Compact')],
        ],
        'radius' => [
            'label' => __('Radius'),
            'current' => $radius,
            'options' => ['' => __('Saved'), 'rounded' => __('Rounded'), 'subtle' => __('Subtle'), 'square' => __('Square')],
        ],
        'sidebar' => [
            'label' => __('Sidebar'),
            'current' => $sidebarSize,
            'options' => ['' => __('Saved'), 'standard' => __('Standard'), 'compact' => __('Compact'), 'dense' => __('Dense')],
        ],
    ];
@endphp

{{-- The root covers the content area so a forced dark mode paints the whole page. No light bg-white here:
     the body already paints it, and a host stylesheet loaded later would let bg-white beat dark:bg-gray-900. --}}
<div
    x-data="auraStyleguide({ forceLight: @js($mode === 'light') })"
    data-styleguide
    data-styleguide-mode="{{ $mode ?: 'admin' }}"
    @class([
        '-m-5 p-5 pb-24 min-h-screen md:-m-8 md:p-8 md:pb-24 dark:bg-gray-900 dark:text-gray-100 dark:[color-scheme:dark]',
        'dark' => $mode === 'dark',
    ])
    @if ($tokenStyle !== '') style="{{ $tokenStyle }}" @endif
>

    @if ($section === '')
        {{-- Page header --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Style Guide') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                {{ __('Every UI component in Aura on one page, rendered with the real Blade components wherever possible. Improve a component here and the whole admin follows.') }}
            </p>
        </div>

        {{-- Preview controls: plain links, so every state has a shareable URL. --}}
        <div class="flex flex-wrap gap-x-6 gap-y-3 items-center mt-6" data-styleguide-controls>
            @foreach ($previewControls as $param => $control)
                <div class="flex gap-2 items-center">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $control['label'] }}</span>
                    <div class="inline-flex gap-0.5 items-center p-0.5 rounded-lg bg-gray-950/[0.04] dark:bg-white/5">
                        @foreach ($control['options'] as $value => $label)
                            <a href="{{ $this->urlWith([$param => $value]) }}"
                                data-styleguide-control="{{ $param }}:{{ $value === '' ? 'default' : $value }}"
                                @class([
                                    'px-2.5 py-1 text-xs rounded-md transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500',
                                    'bg-white text-gray-900 font-medium shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10' => $control['current'] === $value,
                                    'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200' => $control['current'] !== $value,
                                ])>{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
            {{ __('Previews apply to this page only and are never saved. Light mode turns the admin\'s dark class off while this page is open.') }}
        </p>

        {{-- Anchor navigation --}}
        <nav class="sticky top-0 z-[5] -mx-4 px-4 py-3 mt-6 bg-white/95 backdrop-blur-md border-b border-gray-950/5 dark:bg-gray-900/95 dark:border-white/10" aria-label="{{ __('Style guide sections') }}">
            <div class="flex flex-wrap gap-1">
                @foreach ($sections as $id => $meta)
                    <a href="#{{ $id }}"
                        class="px-2.5 py-1.5 text-sm rounded-md text-gray-500 transition hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5">
                        <span class="text-xs tabular-nums text-gray-400 dark:text-gray-500">{{ sprintf('%02d', $loop->iteration) }}</span>
                        {{ $meta['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    @foreach ($visibleSections as $id => $meta)
        <section id="{{ $id }}" data-styleguide-section="{{ $id }}" class="{{ $section === '' ? 'mt-16' : '' }} scroll-mt-20">
            <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">{{ sprintf('%02d', array_search($id, $sectionIds, true) + 1) }}</span>
            <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ $meta['label'] }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">{{ $meta['description'] }}</p>

            @include('aura::styleguide.sections.'.$id)
        </section>
    @endforeach

</div>
