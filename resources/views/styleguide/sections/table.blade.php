@php
    $statusClasses = [
        'Published' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        'Draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'In Review' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
        'Failed' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
    ];

    $tableRows = [
        ['id' => 1, 'title' => 'Getting started with Aura', 'status' => 'Published', 'author' => 'Bajram Emini', 'date' => '2026-07-01', 'state' => 'default'],
        ['id' => 2, 'title' => 'Designing resource fields', 'status' => 'Draft', 'author' => 'Sarah Keller', 'date' => '2026-07-04', 'state' => 'selected'],
        ['id' => 3, 'title' => 'Team permissions explained', 'status' => 'In Review', 'author' => 'Jonas Weber', 'date' => '2026-07-08', 'state' => 'hover'],
        ['id' => 4, 'title' => 'Publishing workflows', 'status' => 'Failed', 'author' => 'Mia Brunner', 'date' => '2026-07-10', 'state' => 'default'],
    ];

    $segmentActive = 'bg-white text-gray-900 font-medium shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10';
    $segmentIdle = 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200';
    $rowAction = 'inline-flex items-center p-1.5 text-gray-400 rounded-lg transition-colors duration-150 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500';
    $paginator = $this->demoPaginator();
@endphp

{{-- Toolbar (specimen of livewire/table.blade.php) --}}
<div class="mt-6 aura-table-toolbar" data-table-part="toolbar" x-data="{ toggleFilters() {} }">
    <div class="aura-table-toolbar-search">
        <div class="relative">
            <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
            </div>
            <input type="text" class="aura-field aura-control-base block w-full pl-9 pr-3 text-sm text-gray-900 bg-white border-0 ring-1 shadow-xs transition appearance-none placeholder:text-gray-400 dark:placeholder:text-gray-500 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 focus:outline-none focus:ring-2 focus:ring-primary-500 z-[1]" placeholder="{{ __('Search for items') }}">
        </div>
    </div>
    <div class="space-x-4 aura-table-toolbar-actions">
        <div class="flex space-x-2">
            <div class="flex aura-control-base">
                <div class="inline-flex isolate gap-0.5 items-stretch p-0.5 h-full rounded-lg bg-gray-950/[0.04] dark:bg-white/5">
                    <button type="button" class="inline-flex relative justify-center items-center rounded-md aspect-square {{ $segmentActive }}"><span class="sr-only">{{ __('List view') }}</span><x-aura::icon icon="list" size="sm" /></button>
                    <button type="button" class="inline-flex relative justify-center items-center rounded-md aspect-square {{ $segmentIdle }}"><span class="sr-only">{{ __('Grid view') }}</span><x-aura::icon icon="grid" size="sm" /></button>
                    <button type="button" class="inline-flex relative justify-center items-center rounded-md aspect-square {{ $segmentIdle }}"><span class="sr-only">{{ __('Kanban view') }}</span><x-aura::icon icon="kanban" size="sm" /></button>
                </div>
            </div>
            <x-aura::button.border>
                <x-slot:icon><x-aura::icon icon="table" size="sm" /></x-slot:icon>
                {{ __('Settings') }}
            </x-aura::button.border>
            @include('aura::components.table.filter')
        </div>
        <button type="button" class="aura-button aura-control-base inline-flex gap-1.5 items-center px-3 text-sm font-medium text-gray-700 bg-white ring-1 shadow-xs transition-colors duration-150 ring-gray-950/10 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-gray-700/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
            {{ __('Actions') }}
            <svg class="-mr-0.5 w-4 h-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
        </button>
    </div>
</div>

{{-- Filter tabs (specimen of table/filter-tabs) --}}
<div class="flex flex-wrap gap-1 items-center p-1 my-4 max-w-full rounded-lg bg-gray-950/[0.04] dark:bg-white/5 w-fit" data-table-part="filter-tabs">
    <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm rounded-md transition-colors duration-150 cursor-pointer {{ $segmentActive }}">{{ __('All') }}</button>
    <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm rounded-md transition-colors duration-150 cursor-pointer {{ $segmentIdle }}">{{ __('My drafts') }}</button>
    <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm rounded-md transition-colors duration-150 cursor-pointer {{ $segmentIdle }}">{{ __('Published') }}</button>
</div>

{{-- List view (specimen of table/list-view, table-header and row). Headings are the real table.heading. --}}
<div class="overflow-hidden bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10" data-table-part="list">
    <div class="overflow-x-auto min-w-full align-middle">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50/80 dark:bg-white/[0.03] border-b border-gray-200/80 dark:border-white/10">
                    <x-aura::table.heading class="pr-0 w-8">
                        <x-aura::input.checkbox hideLabel :label="__('Select page')" />
                    </x-aura::table.heading>
                    <x-aura::table.heading sortable direction="asc">{{ __('Title') }}</x-aura::table.heading>
                    <x-aura::table.heading sortable>{{ __('Status') }}</x-aura::table.heading>
                    <x-aura::table.heading>{{ __('Author') }}</x-aura::table.heading>
                    <x-aura::table.heading sortable direction="desc">{{ __('Created at') }}</x-aura::table.heading>
                    <th class="table-row-actions px-6 py-2.5"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            @foreach ($tableRows as $row)
                <tbody @class(['divide-y divide-gray-100 dark:divide-white/5', 'border-t border-gray-100 dark:border-white/5' => ! $loop->first, 'sg-force-hover' => $row['state'] === 'hover'])>
                    <tr data-row-state="{{ $row['state'] }}" @class([
                        'transition-colors duration-150 ease-in-out cm-table-row hover:bg-gray-50/80 dark:hover:bg-white/[0.04]',
                        'bg-primary-50/60 dark:bg-primary-500/10' => $row['state'] === 'selected',
                    ])>
                        <td class="relative py-3 pr-0 pl-6">
                            @if ($row['state'] === 'selected')
                                <span class="absolute inset-y-0 left-0 w-0.5 bg-primary-600" aria-hidden="true"></span>
                            @endif
                            <x-aura::input.checkbox hideLabel :label="'Row '.$row['id']" :checked="$row['state'] === 'selected'" />
                        </td>
                        <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $row['title'] }}
                            @if ($row['state'] !== 'default')
                                <span class="ml-2 text-xs font-normal text-gray-400 dark:text-gray-500">({{ $row['state'] }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm">
                            <span class="inline-flex items-center gap-x-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-gray-950/10 dark:ring-white/10 {{ $statusClasses[$row['status']] }}">
                                <svg class="size-1.5 shrink-0 fill-current opacity-70" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                                {{ $row['status'] }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['author'] }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['date'] }}</td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end items-center pr-2 space-x-1">
                                <x-aura::tippy :text="__('View')" position="top">
                                    <a href="#table" class="{{ $rowAction }}"><x-aura::icon icon="view" size="xs" /><span class="sr-only">{{ __('View') }}</span></a>
                                </x-aura::tippy>
                                <x-aura::tippy :text="__('Edit')" position="top">
                                    <a href="#table" class="{{ $rowAction }}"><x-aura::icon icon="edit" size="xs" /><span class="sr-only">{{ __('Edit') }}</span></a>
                                </x-aura::tippy>
                            </div>
                        </td>
                    </tr>
                </tbody>
            @endforeach
        </table>
    </div>
</div>

{{-- Pagination: the real table/pagination view with a demo paginator. --}}
<div class="mt-4 w-full" data-table-part="pagination">
    {{ $paginator->links('aura::components.table.pagination') }}
</div>

<div class="grid gap-4 mt-6 lg:grid-cols-2">
    {{-- Bulk selection bar (specimen of table/bulk-select-row; floats over the table bottom in the app) --}}
    <div data-table-part="bulk-select">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Selection bar') }}</span>
        <div class="px-4 py-3 mt-3 w-full text-sm text-gray-800 rounded-xl ring-1 shadow-lg backdrop-blur-md bg-white/85 ring-gray-950/10 dark:bg-gray-800/85 dark:ring-white/10 dark:text-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <span>{{ __('You have selected') }} <strong>1</strong> {{ __('row') }}.</span>
                    <x-aura::button.border size="xs">{{ __('Select all') }}</x-aura::button.border>
                </div>
                <x-aura::button.border size="xs">{{ __('Clear selection') }}</x-aura::button.border>
            </div>
        </div>
    </div>

    {{-- Empty state (specimen of table/list-view) --}}
    <div data-table-part="empty">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Empty state') }}</span>
        <div class="overflow-hidden mt-3 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <div class="flex flex-col items-center px-6 py-12 text-center">
                <div class="flex justify-center items-center w-12 h-12 bg-gray-50 rounded-full ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/10">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('No entries available') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Entries you create will show up here.') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 aura-card" data-table-part="anatomy">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Anatomy') }}</span>
    <dl class="grid gap-x-8 gap-y-2 mt-4 text-sm md:grid-cols-2">
        @foreach ([
            'livewire/table' => __('Toolbar at base height: search, view switch, settings, filters, bulk actions'),
            'table/filter-tabs' => __('Saved filters as segmented tabs; hidden when there are none'),
            'table/list-view' => __('Card, table, empty state; includes header, rows and footer'),
            'table/table-header + table/heading' => __('Header row; sortable headings show the sort arrow'),
            'table/row + table/row-actions' => __('Row with checkbox, cells and view/edit actions'),
            'table/bulk-select-row' => __('Floating selection bar while rows are selected'),
            'table/footer + table/pagination' => __('Pagination below the card'),
            'table/settings, table/filters' => __('Column picker popover and filter slide-over'),
        ] as $file => $role)
            <div class="flex gap-3">
                <dt class="shrink-0 w-56"><code class="text-xs text-gray-700 dark:text-gray-200">{{ $file }}</code></dt>
                <dd class="text-gray-500 dark:text-gray-400">{{ $role }}</dd>
            </div>
        @endforeach
    </dl>
    <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Row selection and sorting need a live Table component, so the rows above are static copies of table/row. Each resource configures its table in indexTableSettings() (search, filters, selectable, actions, views); see docs/table.md.') }}</p>
</div>
