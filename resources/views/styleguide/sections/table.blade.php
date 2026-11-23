@php
    $segmentActive = 'bg-white text-gray-900 font-medium shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10';
    $segmentIdle = 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200';
@endphp

{{-- The real resource table (livewire/table) on the Users resource: toolbar, column headings with
     their type icons, avatars, row actions on hover, selection and pagination all work. --}}
<div class="mt-2" data-table-part="live">
    <livewire:aura::table
        :model="app(config('aura.resources.user'))"
        :settings="['per_page' => 5, 'create' => false, 'resource_editor' => false]"
        wire:key="styleguide-users-table"
    />
</div>

<span class="block mt-10 text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Saved filters') }}</span>
{{-- Filter tabs (specimen of table/filter-tabs) --}}
<div class="flex flex-wrap gap-1 items-center p-1 mt-3 mb-4 max-w-full aura-segmented bg-gray-950/[0.04] dark:bg-white/5 w-fit" data-table-part="filter-tabs">
    <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm aura-segmented-item transition-colors duration-150 cursor-pointer {{ $segmentActive }}">{{ __('All') }}</button>
    <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm aura-segmented-item transition-colors duration-150 cursor-pointer {{ $segmentIdle }}">{{ __('My drafts') }}</button>
    <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm aura-segmented-item transition-colors duration-150 cursor-pointer {{ $segmentIdle }}">{{ __('Published') }}</button>
</div>

<div class="grid gap-4 mt-6 lg:grid-cols-2">
    {{-- Bulk selection bar (specimen of table/bulk-select-row; floats over the table bottom in the app) --}}
    <div data-table-part="bulk-select">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Selection bar') }}</span>
        <div class="px-4 py-3 mt-3 w-full text-sm text-gray-800 aura-radius-panel ring-1 shadow-lg backdrop-blur-md bg-white/85 ring-gray-950/10 dark:bg-gray-800/85 dark:ring-white/10 dark:text-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <span>{{ __('You have selected') }} <strong>1</strong> {{ __('row') }}.</span>
                    <x-aura::button.border size="xs">{{ __('Select all') }}</x-aura::button.border>
                </div>
                <x-aura::button.border size="xs">{{ __('Clear selection') }}</x-aura::button.border>
            </div>
        </div>
    </div>

    {{-- Empty state (specimen of table/list-view): no card, the resource icon in a tile, one action. --}}
    <div data-table-part="empty">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Empty state') }}</span>
        <div class="flex flex-col items-center px-6 py-10 mt-3 text-center">
            <div class="flex justify-center items-center mb-3 w-10 h-10 aura-radius-control bg-gray-950/[0.04] text-gray-400 dark:bg-white/5 dark:text-gray-500 [&>svg]:size-5" aria-hidden="true">
                {!! app(config('aura.resources.user'))->getIcon() !!}
            </div>
            <h3 class="aura-section-title">{{ __('No :resources yet', ['resources' => __('users')]) }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Create the first one to get started.') }}</p>
            <div class="mt-5">
                <x-aura::button><x-slot:icon><x-aura::icon icon="plus" /></x-slot>{{ __('New :resource', ['resource' => __('user')]) }}</x-aura::button>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 aura-card" data-table-part="anatomy">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Anatomy') }}</span>
    <dl class="grid gap-x-8 gap-y-2 mt-4 text-sm md:grid-cols-2">
        @foreach ([
            'table/header' => __('Page title with the record count (24 px); a relation table gets a section title (14 px) instead'),
            'livewire/table' => __('One toolbar row at base height: search grows, view switch, columns, filters and bulk actions keep their size'),
            'table/filter-tabs' => __('Saved filters as segmented tabs; hidden when there are none'),
            'table/list-view' => __('No card: a hairline under the header, row dividers and a closing hairline carry the list; empty state'),
            'table/table-header + table/heading' => __('Sentence-case 12 px headings with the column-type icon; sortable headings show the arrow'),
            'table/row + table/row-actions' => __('The row opens the record; view and edit appear on hover or focus. Relations show avatar + title (x-aura::avatar)'),
            'table/bulk-select-row' => __('Floating selection bar while rows are selected'),
            'table/footer + table/pagination' => __('Pagination below the list, sm (32 px) buttons'),
            'table/settings, table/filters' => __('Column picker popover and filter slide-over'),
        ] as $file => $role)
            <div class="flex gap-3">
                <dt class="shrink-0 w-56"><code class="text-xs text-gray-700 dark:text-gray-200">{{ $file }}</code></dt>
                <dd class="text-gray-500 dark:text-gray-400">{{ $role }}</dd>
            </div>
        @endforeach
    </dl>
    <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('The table above is the live component, so sorting, search, selection and the column picker work. Each resource configures its table in indexTableSettings() (search, filters, selectable, actions, views); see docs/table.md.') }}</p>
</div>
