<div>
    @section('title', __('Plugins'))

    {{-- Page header as on index pages: the trail "Settings / Plugins" from sm (a back link to Settings on
         phones), title with the number of Aura plugins, the installed Aura CMS version under it. --}}
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
        @if (Route::has('aura.settings'))
            <x-aura::breadcrumbs.li :href="route('aura.settings')" :title="__('Settings')" />
        @endif
        <x-aura::breadcrumbs.li :title="__('Plugins')" />
    </x-aura::breadcrumbs>

    {{-- The title sits in the 54px band every page title shares; the version line goes under it. --}}
    <div class="flex items-center mt-1 min-h-9 sm:mt-8 sm:items-end sm:min-h-[3.375rem]">
        <h1 class="aura-page-title">
            {{ __('Plugins') }}
            @if ($pluginCount)
                <span class="aura-page-count">{{ number_format($pluginCount) }}</span>
            @endif
        </h1>
    </div>
    {{-- The core package is the CMS itself: its version is the page meta, not a plugin row. --}}
    @if ($coreVersion)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" data-core-version>
            {{ __('Aura CMS') }} <span class="font-mono text-[13px] tabular-nums text-gray-600 dark:text-gray-300">{{ $coreVersion }}</span>
        </p>
    @endif

    @php
        $composerHint = strtr(e(__('Plugins are managed with Composer. Run :command to install or update one.')), [
            ':command' => '<code class="aura-code">composer require vendor/plugin</code>',
        ]);
    @endphp

    {{-- Aura's own plugins first, in the shared list look (components/table/list-view): hairline rows, fixed
         column widths, descriptions clamped to two lines. Without plugins the section says so and how to add
         one. The other Composer packages follow in a closed disclosure with their count. --}}
    <section class="mt-6" data-plugins-section>
        <h2 class="aura-section-title">{{ __('Aura plugins') }}</h2>

        @if ($plugins === [])
            <div class="py-5 mt-2 border-t border-b border-gray-200/80 dark:border-white/10" data-plugins-empty>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('No plugins installed') }}</p>
                <p class="mt-1 text-sm text-gray-500 aura-code-note dark:text-gray-400">{!! $composerHint !!}</p>
            </div>
        @else
            <p class="mt-1 text-sm text-gray-500 aura-code-note dark:text-gray-400">{!! $composerHint !!}</p>
            @include('aura::livewire.plugins-page-table', ['packages' => $plugins, 'keyPrefix' => 'plugin'])
        @endif
    </section>

    @if ($otherPackages !== [])
        <details class="mt-8 group/other" data-other-packages>
            <summary class="inline-flex gap-1.5 items-center -mx-1 px-1 rounded-md list-none cursor-pointer select-none [&::-webkit-details-marker]:hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                <x-aura::icon.caret direction="right" class="text-gray-500 transition-transform duration-150 size-4 group-open/other:rotate-90 dark:text-gray-400" />
                <span class="aura-section-title">{{ __('Other packages') }}</span>
                <span class="text-sm font-normal tabular-nums text-gray-500 dark:text-gray-400">{{ number_format(count($otherPackages)) }}</span>
            </summary>

            @include('aura::livewire.plugins-page-table', ['packages' => $otherPackages, 'keyPrefix' => 'package'])
        </details>
    @endif
</div>
