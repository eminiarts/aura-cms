<div>
    @section('title', __('Plugins'))

    {{-- Page header as on index pages: breadcrumb from sm, title with the count, a short line under it. --}}
    <div class="hidden sm:block">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
            <x-aura::breadcrumbs.li :title="__('Plugins')" />
        </x-aura::breadcrumbs>
    </div>

    <div class="mt-1 sm:mt-6">
        <div class="flex items-center min-h-9">
            <h1 class="aura-page-title">
                {{ __('Plugins') }}
                <span class="aura-page-count">{{ number_format(count($installedPackages)) }}</span>
            </h1>
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {!! strtr(e(__('Plugins are managed with Composer. Run :command to install or update one.')), [
                ':command' => '<code class="aura-code">composer require vendor/plugin</code>',
            ]) !!}
        </p>
    </div>

    {{-- Two sections, Aura's own plugins first, then the other Composer packages. Each uses the shared list
         look (components/table/list-view): small gray headings, hairline rows. Fixed column widths keep
         both tables aligned; descriptions clamp to two lines on every row. --}}
    @foreach ($sections as $title => $packages)
        <section class="mt-8 first-of-type:mt-6" wire:key="plugins-section-{{ $loop->index }}">
            <h2 class="flex gap-2 items-baseline aura-section-title">
                {{ $title }}
                <span class="text-sm font-normal tabular-nums text-gray-500 dark:text-gray-400">{{ number_format(count($packages)) }}</span>
            </h2>

            <div class="hidden mt-2 sm:block">
                <table class="min-w-full table-fixed">
                    <colgroup>
                        <col class="w-[36%]">
                        <col class="w-32">
                        <col>
                    </colgroup>
                    <thead>
                        <tr class="border-b border-gray-200/80 dark:border-white/10">
                            <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Package') }}</th>
                            <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Version') }}</th>
                            <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody class="border-b divide-y divide-gray-200/60 border-gray-200/80 dark:divide-white/[0.06] dark:border-white/10">
                        @foreach ($packages as $name => $package)
                            <tr wire:key="plugin-{{ $name }}">
                                <td class="px-4 py-3 text-sm align-top">
                                    <div class="flex gap-2 items-center min-w-0 leading-6">
                                        <span class="font-medium text-gray-900 truncate dark:text-white" title="{{ $name }}">{{ $name }}</span>
                                        @if ($package['dev'] ?? false)
                                            <span class="aura-tag shrink-0">{{ __('Dev') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm align-top whitespace-nowrap">
                                    <div class="flex items-center h-6">
                                        <span class="tabular-nums aura-tag">{{ $package['version'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm leading-6 align-top text-gray-600 dark:text-gray-300">
                                    <p class="line-clamp-2" title="{{ $package['description'] }}">{{ $package['description'] ?: __('No description available.') }}</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Phones: the stacked rows of the other index pages, package and version on one line (a long name
                 wraps at its slash or hyphen, not cut mid-word), the description clamped to two. --}}
            <ul class="mt-2 border-t border-b divide-y divide-gray-200/60 border-gray-200/80 sm:hidden dark:divide-white/[0.06] dark:border-white/10">
                @foreach ($packages as $name => $package)
                    <li class="py-3" wire:key="plugin-mobile-{{ $name }}">
                        <div class="flex gap-3 justify-between items-start min-w-0">
                            <span class="min-w-0 text-sm font-medium text-gray-900 [overflow-wrap:anywhere] dark:text-white">{{ $name }}</span>
                            <span class="flex gap-1.5 items-center shrink-0">
                                @if ($package['dev'] ?? false)
                                    <span class="aura-tag">{{ __('Dev') }}</span>
                                @endif
                                <span class="tabular-nums aura-tag">{{ $package['version'] }}</span>
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500 line-clamp-2 dark:text-gray-400">{{ $package['description'] ?: __('No description available.') }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
