<div
    data-attachment-table
    @selectfieldrows.window="selectRows($event.detail)"
    x-data="{
    selected: @entangle('selected'),
    rows: @js($rowIds),
    lastSelectedId: null,
    total: @js($rows->total()),
    selectPage: false,
    currentPage: @entangle('paginators.page'),
    selectAll: @entangle('selectAll'),
    loading: false,
    oldSelected: null,
    field: @js($field),
    maxFilesReached: false,
    _updatingFromSelectedRows: false,

    init() {
        Livewire.on('selectedRows', (updatedSelected) => {
            // Only update if values are actually different to prevent circular updates
            const newSelected = updatedSelected[0] || [];
            const currentSelected = this.selected || [];

            // Compare arrays - if they're the same, don't update
            if (JSON.stringify(newSelected.sort()) === JSON.stringify([...currentSelected].sort())) {
                return;
            }

            this._updatingFromSelectedRows = true;
            this.selected = [...newSelected];
            this.$nextTick(() => {
                this._updatingFromSelectedRows = false;
            });
        });

        Livewire.on('rowIdsUpdated', (ids) => {
            this.rows = ids[0];
            this.selectPage = false;
        });

        if (this.selectAll) {
            this.selectPage = true;
        }

        this.$watch('selected', (value) => {
            if (this.field && this.field.max_files) {
                this.maxFilesReached = value.length >= this.field.max_files;
            }
            // Only dispatch selection-changed if NOT from selectedRows to prevent circular updates
            if (!this._updatingFromSelectedRows) {
                this.$dispatch('selection-changed', { selected: value, slug: this.field ? this.field.slug : null });
            }
        });

        this.$watch('rows', (rows) => {
            // Check if rows (array of ids) is included in this.selected. if so, set this.selectPage to true
        });

        this.$watch('currentPage', (rows) => {
            this.$nextTick(() => {
                this.selectPage = this.rows.every(row => this.selected.includes(row));
            });
        });
    },

    toggleRow(event, id) {
        if (!this.rows || !Array.isArray(this.rows)) {
            console.warn('this.rows is not an array, exiting toggleRow');
            return;
        }

        // Use reassignment instead of mutation for proper reactivity with entangle
        const idStr = String(id);

        if (this.field && this.field.max_files === 1) {
            // Single file selection mode
            if (this.selected.includes(idStr)) {
                this.selected = [];
            } else {
                this.selected = [idStr];
            }
        } else if (event.shiftKey && this.lastSelectedId !== null) {
            // Shift-click range selection
            const lastIndex = this.rows.indexOf(this.lastSelectedId);
            const currentIndex = this.rows.indexOf(id);

            if (lastIndex === -1 || currentIndex === -1) {
                console.warn('Invalid indexes, exiting shift selection');
                return;
            }

            const start = Math.min(lastIndex, currentIndex);
            const end = Math.max(lastIndex, currentIndex);
            const rowsToToggle = this.rows.slice(start, end + 1).map(String);

            const isLastSelected = this.selected.includes(String(this.lastSelectedId));

            if (isLastSelected) {
                // Adding rows - use reassignment
                let newSelection = [...new Set([...this.selected, ...rowsToToggle])];
                if (this.field && this.field.max_files) {
                    newSelection = newSelection.slice(0, this.field.max_files);
                }
                this.selected = newSelection;
            } else {
                // Removing rows - use reassignment
                this.selected = this.selected.filter(row => !rowsToToggle.includes(String(row)));
            }
        } else {
            // Single click selection - use reassignment instead of push/splice
            const index = this.selected.indexOf(idStr);
            if (index === -1) {
                if (!this.field || !this.field.max_files || this.selected.length < this.field.max_files) {
                    // Use spread to create new array instead of push
                    this.selected = [...this.selected, idStr];
                } else {
                    console.warn('Max files limit reached, cannot add more');
                }
            } else {
                // Use filter to create new array instead of splice
                this.selected = this.selected.filter((_, i) => i !== index);
            }
        }

        this.lastSelectedId = id;
    },

    selectCurrentPage() {
        this.$nextTick(() => {
            if (this.selectPage) {
                // add this.rows to existing this.selected, unique
                this.selected = Array.from(new Set([...this.selected.map(Number), ...this.rows.map(Number)]));

                // if all rows are selected, set this.selectAll to true
                this.selectAll = this.selected.length === this.total;
            } else {

                this.selectAll = false;

                // remove this.rows from existing this.selected with new Set
                this.selected = [...new Set([...this.selected.map(Number)].filter(item => !this.rows.map(Number).includes(item)))];
            }
        });
    },

    selectAllRows: async function() {

        this.loading = true

        let allSelected = await $wire.getAllTableRows()
        this.selectAll = true

        this.loading = false

        this.$nextTick(() => {
            // this.selected = allSelected with set
            this.selected = [...new Set([...this.selected.map(Number), ...allSelected.map(Number)])];
            this.selectPage = true;
        });
    },

    resetBulk() {
        this.selected = [];
        this.selectPage = false;
        this.selectAll = false;
    },

    deselectRows(ids) {
        for (id of ids) {
            let index = this.selected.indexOf(id)

            if (index === -1) {
                continue
            }

            this.selected.splice(index, 1)
            {{-- this.toggleRow(false, id) --}}
        }
    },

    selectRows(detail) {
        if (detail.slug == '{{ optional($field)['slug'] }}') {
            this.selected = detail.value
        }
    }
}">
    {{-- Be aware that this file opens a div which closes at the end --}}
    @include('aura::components.table.context-menu')

    <main class="" x-data="{
        showAttachmentFilters: false,
        toggleFilters() {
            this.showAttachmentFilters = !this.showAttachmentFilters;
            // this.$dispatch('inset-sidebar', { element: this.$refs.sidebar })
        },
        init() {
            Livewire.dispatch('tableMounted')

            const sortable = new window.Sortable(document.querySelectorAll('.sortable-wrapper'), {
                draggable: '.sortable',
                handle: '.drag-handle'
            });

            sortable.on('sortable:stop', () => {
                setTimeout(() => {
                    @this.reorder(
                        Array.from(document.querySelectorAll('.sortable'))
                        .map(el => el.id)
                    )
                }, 0)
            })
        }
    }">
        @include($this->settings['views']['header'])

        {{-- @dump($this->settings) --}}

        <div class="mt-4">

            {{-- Toolbar, one vocabulary with the media library grid page (plugins): a 14rem search, the type
                 segment, then from the right edge inwards the view switch, Actions, Filters and Columns. The
                 upload date filter sits in the Filters panel (a dot on Filters marks it active). Phones, as on
                 the grid page: the full-width search, then one row with the type as a select, Filters, Actions
                 (with Columns as its first item) and the view switch. From sm the wrappers dissolve
                 (sm:contents) into one wrapping row (sm:order and a spacer put the controls right). The
                 type set stays on one line: when the segmented control no longer fits (the toolbar wraps),
                 it collapses into the same select. --}}
            <div
                class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:gap-2 sm:items-center"
                x-data="{
                    compact: false,
                    wrapped(el) { return !!el && el.offsetHeight > 52 },
                    check() {
                        if (window.matchMedia('(max-width: 639px)').matches) {
                            this.compact = true
                            return
                        }
                        if (! this.compact) {
                            if (this.wrapped(this.$refs.segment) || this.wrapped(this.$root)) {
                                this.compact = true
                            }
                        } else {
                            // Let the segment re-render and settle, then re-test.
                            this.compact = false
                            setTimeout(() => {
                                if (this.wrapped(this.$refs.segment) || this.wrapped(this.$root)) {
                                    this.compact = true
                                }
                            }, 60)
                        }
                    },
                }"
                x-init="
                    check();
                    // Re-check when the toolbar's width changes, including when the
                    // picker's details panel opens; ignore height changes caused by check() itself.
                    let width = $el.parentElement.offsetWidth;
                    new ResizeObserver(Alpine.debounce(() => {
                        if ($el.parentElement.offsetWidth === width) return;
                        width = $el.parentElement.offsetWidth;
                        check();
                    }, 150)).observe($el.parentElement);
                "
            >

                @if ($this->settings['search'])
                    {{-- The media pages' search: 14rem from sm, as on the media library grid page. --}}
                    <div class="w-full sm:w-56 sm:shrink-0">
                        <label for="table-search" class="sr-only">{{ __('Search') }}</label>
                        <div class="relative">
                            <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                <x-aura::icon.search class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" />
                            </div>
                            <input type="text" id="table-search"
                                class="block h-9 w-full py-2 pl-9 pr-3 text-base sm:text-sm text-gray-900 bg-white rounded-lg border-0 ring-1 shadow-xs transition appearance-none placeholder:text-gray-400 dark:placeholder:text-gray-400 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 focus:outline-none focus:ring-2 focus:ring-primary-500 z-[1]"
                                placeholder="{{ __('Search files…') }}" wire:model.live.debounce="search">
                        </div>
                    </div>
                @endif

                @php
                    $activeType = $this->quickFilters['type'] ?? null;
                    $activeMonth = $this->quickFilters['month'] ?? null;
                    $uploadMonths = $model::uploadMonths();

                    $segmentBase = 'px-2.5 py-1 text-sm font-medium rounded-md whitespace-nowrap transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500';
                    $segmentActive = 'bg-white text-gray-900 shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10';
                    $segmentIdle = 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200';
                    $toolSelect = 'h-9 pl-3 pr-9 text-sm text-gray-600 bg-white rounded-lg border transition appearance-none border-gray-500/30 focus:outline-none focus:ring focus:border-primary-300 focus:ring-primary-300 focus:ring-opacity-50 dark:text-gray-300 dark:bg-transparent dark:border-gray-700';
                    // Without the Filters panel the date filter stays a toolbar select.
                    $monthInFilters = $this->settings['filters'];
                    $columnsInActionsMenu = $this->settings['settings'] && $currentView == 'list' && $model->showTableSettings()
                        && $this->settings['bulk_actions'] && $this->bulkActions
                        && ($this->settings['views']['bulk_actions'] ?? null) === 'aura::components.table.bulk-actions';
                @endphp

                {{-- Phone row 2: the type select, Filters, Actions and the view switch. --}}
                <div class="flex gap-2 items-center min-w-0 sm:contents">
                    <div class="flex flex-1 min-w-0 sm:contents" data-media-quick-filters>
                        <div
                            x-ref="segment"
                            x-show="!compact"
                            class="inline-flex gap-0.5 isolate items-center p-0.5 rounded-lg shrink-0 bg-gray-950/[0.04] dark:bg-white/5"
                            role="group" aria-label="{{ __('Media type') }}"
                        >
                            <button
                                type="button"
                                wire:click="setQuickFilter('type', null)"
                                data-type-filter="all"
                                aria-pressed="{{ $activeType === null ? 'true' : 'false' }}"
                                class="{{ $segmentBase }} {{ $activeType === null ? $segmentActive : $segmentIdle }}"
                            >
                                {{ __('All') }}
                            </button>

                            @foreach ($model::MEDIA_TYPES as $typeKey => $typeLabel)
                                <button
                                    type="button"
                                    wire:click="setQuickFilter('type', @js($activeType === $typeKey ? null : $typeKey))"
                                    data-type-filter="{{ $typeKey }}"
                                    aria-pressed="{{ $activeType === $typeKey ? 'true' : 'false' }}"
                                    class="{{ $segmentBase }} {{ $activeType === $typeKey ? $segmentActive : $segmentIdle }}"
                                >
                                    {{ __($typeLabel) }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Compact fallback for narrow toolbars: same filter as a select. --}}
                        <select
                            x-cloak
                            x-show="compact"
                            wire:change="setQuickFilter('type', $event.target.value)"
                            data-type-dropdown
                            aria-label="{{ __('Media type') }}"
                            class="max-sm:flex-1 max-sm:min-w-0 sm:shrink-0 {{ $toolSelect }}"
                        >
                            <option value="" @selected($activeType === null)>{{ __('All types') }}</option>
                            @foreach ($model::MEDIA_TYPES as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" @selected($activeType === $typeKey)>{{ __($typeLabel) }}</option>
                            @endforeach
                        </select>
                    </div>


                    @if (count($uploadMonths) && ! $monthInFilters)
                        <select
                            wire:change="setQuickFilter('month', $event.target.value)"
                            data-month-filter
                            aria-label="{{ __('Upload date') }}"
                            class="min-w-[7.5rem] sm:shrink-0 {{ $toolSelect }}"
                        >
                            <option value="" @selected($activeMonth === null)>{{ __('All dates') }}</option>
                            @foreach ($uploadMonths as $month)
                                <option value="{{ $month }}" @selected($activeMonth === $month)>
                                    {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Saved filters; on phones they are picked in the Filters panel. --}}
                    @if($this->settings['table_before'] && !empty($this->userFilters))
                        <x-aura::input.select
                            wire:model.live="selectedFilter"
                            class="w-40 shrink-0 max-sm:hidden"
                            :options="collect($this->userFilters)->mapWithKeys(function ($filter, $key) {
                                return [$key => $filter['name']];
                            })->prepend(__('All'), '')"
                        />
                    @endif

                    {{-- The media toolbar's controls are h-9; resource tables use h-10. --}}
                    @if ($this->settings['filters'])
                        <div class="relative shrink-0 sm:order-3">
                            @include($this->settings['views']['filter'], ['controlHeight' => 'h-9'])
                            @if ($activeMonth && $monthInFilters)
                                <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full pointer-events-none bg-primary-500" aria-hidden="true"></span>
                            @endif
                        </div>
                    @endif

                    @if ($this->settings['settings'])
                        @if ($currentView == 'list' && $model->showTableSettings())
                            <div class="shrink-0 sm:order-2 max-sm:contents">
                                @include('aura::components.table.settings', ['controlHeight' => 'h-9', 'phoneMenu' => $columnsInActionsMenu ? 'actions' : 'more'])
                            </div>
                        @endif
                        @if ($currentView == 'kanban' && $model->showTableSettings())
                            <div class="shrink-0 sm:order-2">
                                @include('aura::components.table.kanban-settings')
                            </div>
                        @endif
                    @endif

                    @if ($this->settings['bulk_actions'])
                        <div class="shrink-0 sm:order-4">
                            @include($this->settings['views']['bulk_actions'])
                        </div>
                    @endif

                    @if ($this->settings['settings'] || $this->settings['filters'])
                        {{-- The view switch closes the row on the right, as on the media library grid page. --}}
                        <div class="shrink-0 sm:order-5">
                            @include('aura::components.table.switch-view')
                        </div>
                    @endif
                </div>

                {{-- Pushes the list controls to the right edge from sm. --}}
                <div class="hidden sm:block sm:flex-1 sm:order-1" aria-hidden="true"></div>

            </div>

            <div class="aura-table">
                @if($this->settings['selectable'])
                    {{-- A single-value field (e.g. an avatar) never shows selection
                         bars or counts — the one active pick is its own feedback. --}}
                    @if (! ($field && (int) ($field['max_files'] ?? 0) === 1))
                        <div wire:key="table-bulk-select">
                            @include('aura::components.table.bulk-select-row')
                        </div>
                    @endif
                @endif

                @if ($currentView == 'grid')
                    <div class="aura-table-grid-view">
                        @include($this->settings['views']['grid'])
                    </div>
                @elseif($currentView == 'list')
                    <div class="aura-table-list-view">
                        @include($this->settings['views']['table'])
                    </div>
                @elseif($currentView == 'kanban')
                    <div class="aura-table-kanban-view">
                        @include($this->settings['views']['kanban'])
                    </div>
                @endif
            </div>
        </div>

        @if ($this->settings['filters'])
            <x-aura::sidebar.mediamanager title="Filters" show="showAttachmentFilters" in_modal="true">
                <x-slot:heading class="font-semibold">
                    <h3 class="text-2xl font-semibold">
                        {{ __('Filters') }}
                    </h3>
                </x-slot>
                @if (count($uploadMonths))
                    {{-- The media pages' date filter (a quick filter, not a saved filter). --}}
                    <div class="mb-6">
                        <label for="media-month-filter" class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Upload date') }}</label>
                        <select
                            id="media-month-filter"
                            wire:change="setQuickFilter('month', $event.target.value)"
                            data-month-filter
                            class="w-full {{ str_replace('h-9', 'h-10', $toolSelect) }}"
                        >
                            <option value="" @selected($activeMonth === null)>{{ __('All dates') }}</option>
                            @foreach ($uploadMonths as $month)
                                <option value="{{ $month }}" @selected($activeMonth === $month)>
                                    {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @include('aura::components.table.filters')
            </x-aura::sidebar.mediamanager>
        @endif
    </main>
</div> {{-- This closes the context menu --}}
</div>
