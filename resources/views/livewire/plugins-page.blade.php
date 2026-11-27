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

    {{-- The shared list look (components/table/list-view): small gray headings, hairline rows. --}}
    <div class="hidden mt-6 sm:block">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-200/80 dark:border-white/10">
                    <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Package') }}</th>
                    <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Version') }}</th>
                    <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Description') }}</th>
                </tr>
            </thead>
            <tbody class="border-b divide-y divide-gray-200/60 border-gray-200/80 dark:divide-white/[0.06] dark:border-white/10">
                @foreach ($installedPackages as $name => $package)
                    <tr wire:key="plugin-{{ $name }}">
                        <td class="px-4 py-3 text-sm font-medium leading-6 text-gray-900 whitespace-nowrap dark:text-white">{{ $name }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <span class="tabular-nums aura-tag">{{ $package['version'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $package['description'] ?: __('No description available.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Phones: the stacked rows of the other index pages, package and version on one line, the description clamped to two. --}}
    <ul class="mt-4 border-t border-b divide-y divide-gray-200/60 border-gray-200/80 sm:hidden dark:divide-white/[0.06] dark:border-white/10">
        @foreach ($installedPackages as $name => $package)
            <li class="py-3" wire:key="plugin-mobile-{{ $name }}">
                <div class="flex gap-3 justify-between items-center min-w-0">
                    <span class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ $name }}</span>
                    <span class="tabular-nums aura-tag shrink-0">{{ $package['version'] }}</span>
                </div>
                <p class="mt-0.5 text-sm text-gray-500 line-clamp-2 dark:text-gray-400">{{ $package['description'] ?: __('No description available.') }}</p>
            </li>
        @endforeach
    </ul>
</div>
