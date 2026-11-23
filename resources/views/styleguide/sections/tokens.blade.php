@php
    $tokenGroups = $this->designTokens();
    $savedTheme = \Aura\Base\ThemeTokens::resolve(app('aura')::getOption('settings') ?? []);
    $expertMode = filter_var($savedTheme['appearance-expert-mode'] ?? false, FILTER_VALIDATE_BOOL);
    // What the saved settings resolve to, including expert overrides.
    $savedTokens = \Aura\Base\ThemeTokens::design($savedTheme);
    $previewed = [
        'interface-density' => $density,
        'corner-radius' => $radius,
        'sidebar-size' => $sidebarSize,
    ];

    $px = function (string $value): string {
        if (! str_ends_with($value, 'rem')) {
            return $value;
        }

        return rtrim(rtrim(number_format((float) $value * 16, 2, '.', ''), '0'), '.').'px';
    };

    // Inline variable declarations for a specimen rendered with one preset.
    $presetStyle = fn (array $tokens): string => collect($tokens)->map(fn (string $value, string $variable): string => "{$variable}: {$value}")->implode('; ');
@endphp

<div class="mt-6 space-y-4">
    @foreach ($tokenGroups as $setting => $group)
        @php($active = $previewed[$setting] !== '' ? $previewed[$setting] : ($savedTheme[$setting] ?? $group['default']))

        <div class="overflow-hidden bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10" data-token-group="{{ $setting }}">
            <div class="flex flex-wrap gap-x-3 gap-y-1 items-baseline px-5 py-3 border-b border-gray-950/5 dark:border-white/10">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $group['label'] }}</h3>
                <code class="text-xs text-gray-500 dark:text-gray-400">{{ $setting }}</code>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('default') }}: {{ $group['default'] }} · {{ $previewed[$setting] !== '' ? __('previewing') : __('saved') }}: {{ $active }}@if ($expertMode) · {{ __('expert mode on') }}@endif</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50/80 dark:bg-white/[0.03] border-b border-gray-200/80 dark:border-white/10">
                            <x-aura::table.heading>{{ __('Variable · expert setting') }}</x-aura::table.heading>
                            @foreach (array_keys($group['presets']) as $preset)
                                <x-aura::table.heading>
                                    {{ $preset }}@if ($preset === $active) <span class="ml-1 text-primary-600 dark:text-primary-400">●</span>@endif
                                </x-aura::table.heading>
                            @endforeach
                            <x-aura::table.heading>{{ $expertMode ? __('Saved (expert)') : __('Saved') }}</x-aura::table.heading>
                            <x-aura::table.heading>{{ __('Read by') }}</x-aura::table.heading>
                            <x-aura::table.heading>{{ __('Live value') }}</x-aura::table.heading>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($group['variables'] as $variable => $meta)
                            <tr>
                                <td class="px-6 py-2.5 text-sm whitespace-nowrap">
                                    <code class="block text-xs font-semibold text-gray-900 dark:text-white">{{ $variable }}</code>
                                    <code class="block mt-0.5 text-[11px] text-gray-400 dark:text-gray-500" title="{{ __('Expert mode setting, in px') }}">{{ $meta['expert'] ?? '—' }}</code>
                                </td>
                                @foreach ($group['presets'] as $preset => $tokens)
                                    <td @class(['px-6 py-2.5 text-sm whitespace-nowrap tabular-nums', 'text-gray-900 dark:text-white font-medium' => $preset === $active, 'text-gray-600 dark:text-gray-300' => $preset !== $active])>
                                        {{ $tokens[$variable] }} <span class="text-xs text-gray-400 dark:text-gray-500">{{ $px($tokens[$variable]) }}</span>
                                    </td>
                                @endforeach
                                <td class="px-6 py-2.5 text-sm whitespace-nowrap tabular-nums text-gray-600 dark:text-gray-300" data-saved-token="{{ $variable }}">{{ $savedTokens[$variable] ?? '—' }}</td>
                                <td class="px-6 py-2.5 min-w-56 text-xs text-gray-500 dark:text-gray-400">{{ $meta['usedBy'] }}</td>
                                <td class="px-6 py-2.5 text-sm whitespace-nowrap tabular-nums" x-data="auraCssVar(@js($variable))">
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="value" data-live-token="{{ $variable }}"></span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="p-4 text-sm text-gray-600 bg-gray-50 rounded-lg ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
        <p>
            <span class="font-semibold text-gray-900 dark:text-white">{{ __('Expert mode') }}</span>
            (<code class="text-xs">appearance-expert-mode</code>, {{ $expertMode ? __('on for this site') : __('off for this site') }})
            {{ __('replaces any variable above with a pixel value from 0 to 96. When it is off, the presets apply unchanged and the expert values are kept for later. "Live value" shows what this page actually uses.') }}
        </p>
        <p class="mt-2">
            {{ __('No token exists for control height, input radius, layout gaps outside Settings, modal, dropdown or toast radius. Those components use fixed Tailwind classes; see Control sizing.') }}
        </p>
    </div>
</div>

{{-- Specimens: each preset rendered side by side with its own variables. --}}
<div class="grid gap-4 mt-6 xl:grid-cols-2">
    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Interface density') }}</span>
        <div class="mt-4 space-y-5">
            @foreach ($tokenGroups['interface-density']['presets'] as $preset => $tokens)
                <div style="{{ $presetStyle($tokens) }}">
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $preset }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('padding') }} {{ $px($tokens['--aura-panel-padding']) }} · {{ __('gap') }} {{ $px($tokens['--aura-layout-gap']) }}</span>
                    </div>
                    <div class="grid grid-cols-2 mt-2" style="gap: var(--aura-layout-gap)">
                        @foreach ([1, 2] as $panel)
                            <div class="aura-card" style="background-color: rgb(var(--primary-500) / 0.12)">
                                <div class="flex justify-center items-center h-10 text-xs text-gray-500 bg-white rounded-md ring-1 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/10">{{ __('content') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Tinted area = --aura-panel-padding. Space between panels = --aura-layout-gap.') }}</p>
        </div>
    </div>

    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Corner radius') }}</span>
        <div class="mt-4 space-y-5">
            @foreach ($tokenGroups['corner-radius']['presets'] as $preset => $tokens)
                <div style="{{ $presetStyle($tokens) }}">
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $preset }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('panel') }} {{ $px($tokens['--aura-panel-radius']) }} · {{ __('control') }} {{ $px($tokens['--aura-control-radius']) }}</span>
                    </div>
                    <div class="flex gap-3 items-center p-3 mt-2 aura-card">
                        <x-aura::button size="sm">{{ __('Button') }}</x-aura::button>
                        <x-aura::button.border size="sm">{{ __('Border') }}</x-aura::button.border>
                        <div class="w-28">
                            <x-aura::input.text placeholder="{{ __('Input') }}" />
                        </div>
                    </div>
                </div>
            @endforeach
            <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Panels (.aura-card) and buttons (.aura-button) follow the preset. The input keeps rounded-lg (8px) in every preset. A host stylesheet loaded after Aura\'s cannot override the token radius or padding.') }}</p>
        </div>
    </div>

    <div class="aura-card xl:col-span-2">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Sidebar size') }}</span>
        <div class="grid grid-cols-3 gap-3 mt-4">
            @foreach ($tokenGroups['sidebar-size']['presets'] as $preset => $tokens)
                <div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $preset }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $px($tokens['--aura-sidebar-item-height']) }}</span>
                    </div>
                    <div class="mt-2 aura-sidebar open-sidebar aura-sidebar-type-dark aura-sidebar-darkmode-type-dark" style="{{ $presetStyle($tokens) }}">
                        <div class="overflow-hidden p-2 rounded-lg aura-sidebar-bg">
                            <x-aura::navigation.heading>{{ __('Content') }}</x-aura::navigation.heading>
                            <div class="flex flex-col aura-sidebar-groups">
                                <x-aura::navigation.item href="#tokens" class="is-active">
                                    <x-aura::icon icon="dashboard" size="base" />
                                    <span>{{ __('Home') }}</span>
                                </x-aura::navigation.item>
                                <x-aura::navigation.item href="#tokens">
                                    <x-aura::icon icon="collection" size="base" />
                                    <span>{{ __('Posts') }}</span>
                                </x-aura::navigation.item>
                                <x-aura::navigation.item href="#tokens">
                                    <x-aura::icon icon="user" size="base" />
                                    <span>{{ __('Users') }}</span>
                                </x-aura::navigation.item>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Item height, padding, icon size and gaps come from --aura-sidebar-*. The sidebar width is 18rem for standard and 14rem for compact and dense (app.css, not a token).') }}</p>
    </div>
</div>
