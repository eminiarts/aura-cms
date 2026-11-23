<div>
    @section('title', __('Plugins'))

    <div class="mb-6">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
            <x-aura::breadcrumbs.li :title="__('Plugins')" />
        </x-aura::breadcrumbs>
    </div>

    <div class="mb-6">
        <h1 class="mb-2 text-2xl font-semibold">{{ __('Plugins') }}</h1>
    </div>

    <div class="mb-6 aura-alert aura-radius-control">
        {{ __('Plugins are managed via Composer. Run') }}
        <code class="px-1.5 py-0.5 text-xs font-semibold aura-chip aura-radius-control">composer require vendor/plugin</code>
        {{ __('to install or update plugins.') }}
    </div>

    {{-- A framed table (panel radius, header row, row dividers); resource tables use the card-less list (table/list-view). --}}
    <div class="aura-table-card aura-radius-panel overflow-hidden bg-white ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10" data-plugins-table>
        <div class="overflow-x-auto min-w-full align-middle">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50/80 dark:bg-white/[0.03] border-b border-gray-200/80 dark:border-white/10">
                        <x-aura::table.heading>{{ __('Package') }}</x-aura::table.heading>
                        <x-aura::table.heading>{{ __('Version') }}</x-aura::table.heading>
                        <x-aura::table.heading>{{ __('Description') }}</x-aura::table.heading>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($installedPackages as $name => $package)
                        <tr class="transition-colors duration-150 hover:bg-gray-50/80 dark:hover:bg-white/[0.04]">
                            <td class="px-4 aura-table-cell text-sm font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $name }}
                                @if (in_array('laravel', $package['keywords']))
                                    <span class="inline-flex items-center px-2 py-0.5 ml-2 text-xs font-medium rounded-full aura-chip">Laravel</span>
                                @endif
                            </td>
                            <td class="px-4 aura-table-cell text-sm whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium whitespace-nowrap rounded-full aura-chip-primary">{{ $package['version'] }}</span>
                            </td>
                            <td class="px-4 aura-table-cell text-sm text-gray-600 dark:text-gray-300">{{ $package['description'] ?: __('No description available.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-sm text-center text-gray-500 dark:text-gray-400">{{ __('No packages found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
