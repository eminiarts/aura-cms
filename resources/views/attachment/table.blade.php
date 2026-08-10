<div
    data-attachment-table
    @selectfieldrows.window="selectRows($event.detail)"
    x-data="{
    selected: $wire.entangle('selected'),
    exclusions: $wire.entangle('selectAllExclusions'),
    rows: @js($rowIds),
    lastSelectedId: null,
    total: @js($rows->total()),
    selectPage: false,
    currentPage: $wire.entangle('paginators.page'),
    selectAll: $wire.entangle('selectAll'),
    loading: false,
    field: @js($field),
    maxFilesReached: false,
    _updatingFromSelectedRows: false,

    init() {
        Livewire.on('selectedRows', (updatedSelected) => {
            const newSelected = updatedSelected[0] || [];

            if (JSON.stringify(newSelected.map(String).sort()) === JSON.stringify((this.selected || []).map(String).sort())) {
                return;
            }

            this._updatingFromSelectedRows = true;
            this.applySelection({ selected: newSelected, selectAll: false, exclusions: [] });
            this.$nextTick(() => this._updatingFromSelectedRows = false);
        });

        Livewire.on('rowIdsUpdated', (ids) => {
            this.rows = ids[0] || [];
            this.refreshPageSelection();
        });

        this.refreshPageSelection();

        this.$watch('selected', value => {
            this.refreshSelectionLimit();

            if (!this._updatingFromSelectedRows) {
                this.$dispatch('selection-changed', { selected: value, slug: this.field ? this.field.slug : null });
            }
        });

        this.$watch('currentPage', () => {
            this.$nextTick(() => this.refreshPageSelection());
        });
    },

    applySelection(state) {
        this.selected = state.selected || [];
        this.selectAll = Boolean(state.selectAll);
        this.exclusions = state.exclusions || [];
        this.refreshPageSelection();
        this.refreshSelectionLimit();
    },

    contains(ids, id) {
        return (ids || []).some(value => String(value) === String(id));
    },

    isRowSelected(id) {
        return this.selectAll
            ? !this.contains(this.exclusions, id)
            : this.contains(this.selected, id);
    },

    selectionCount() {
        return this.selectAll
            ? Math.max(0, this.total - (this.exclusions || []).length)
            : (this.selected || []).length;
    },

    refreshPageSelection() {
        this.selectPage = this.rows.length > 0 && this.rows.every(id => this.isRowSelected(id));
    },

    refreshSelectionLimit() {
        this.maxFilesReached = Boolean(this.field && this.field.max_files && this.selectionCount() >= this.field.max_files);
    },

    async toggleRow(event, id) {
        let ids = [id];

        if ((!this.field || this.field.max_files !== 1) && event.shiftKey && this.lastSelectedId !== null) {
            const currentIndex = this.rows.findIndex(row => String(row) === String(id));
            const lastIndex = this.rows.findIndex(row => String(row) === String(this.lastSelectedId));

            if (currentIndex !== -1 && lastIndex !== -1) {
                ids = this.rows.slice(Math.min(lastIndex, currentIndex), Math.max(lastIndex, currentIndex) + 1);
            }
        }

        const shouldSelect = !this.isRowSelected(id);

        if (shouldSelect && this.maxFilesReached && (!this.field || this.field.max_files !== 1)) {
            return;
        }

        this.applySelection(await $wire.updateRowSelection(ids, shouldSelect));
        this.lastSelectedId = id;
    },

    async selectCurrentPage() {
        const shouldSelect = ! this.selectPage;

        this.loading = true;
        this.applySelection(await $wire.updateRowSelection(this.rows, Boolean(shouldSelect)));
        this.loading = false;
    },

    async selectAllRows() {
        this.loading = true;
        this.applySelection(await $wire.selectAllRows());
        this.loading = false;
    },

    async resetBulk() {
        this.applySelection(await $wire.clearSelection());
    },

    async deselectRows(ids) {
        this.applySelection(await $wire.updateRowSelection(ids, false));
    },

    selectRows(detail) {
        if (detail.slug == '{{ optional($field)['slug'] }}') {
            this.applySelection({ selected: detail.value, selectAll: false, exclusions: [] });
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

            <div class="flex flex-col justify-between w-full md:items-center md:flex-row">

                @if ($this->settings['search'])
                    <div class="mb-4 w-full md:mb-0 max-w-64">
                        <label for="table-search" class="sr-only">Search</label>
                        <div class="relative mt-1">
                            <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                    viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input type="text" id="table-search"
                                class="block p-2.5 pl-10 w-64 max-w-full rounded-lg shadow-xs transition transition-300 border border-gray-500/30 appearance-none px-3 py-2 focus:outline-none ring-gray-900/10 focus:ring focus:border-primary-300 focus:ring-primary-300  focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 disabled:opacity-75 disabled:bg-gray-100 disabled:opacity-60 disabled:dark:bg-gray-800 bg-white dark:bg-transparent border border-gray-500/30 dark:border-gray-700 dark:focus:border-gray-500 z-[1]"
                                placeholder="{{ __('Search for items') }}" wire:model.live.debounce="search">

                        </div>
                    </div>
                @endif

                {{-- Media type + upload month quick filters --}}
                <div class="flex flex-wrap items-center mb-4 ml-0 space-x-1 w-full md:ml-4 md:mb-0" data-media-quick-filters>
                    @php
                        $activeType = $this->quickFilters['type'] ?? null;
                        $activeMonth = $this->quickFilters['month'] ?? null;
                        $uploadMonths = $model::uploadMonths();
                    @endphp

                    <button
                        type="button"
                        wire:click="setQuickFilter('type', null)"
                        data-type-filter="all"
                        @class([
                            'px-3 py-1.5 text-sm font-medium rounded-full transition',
                            'bg-primary-600 text-white shadow-sm' => $activeType === null,
                            'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => $activeType !== null,
                        ])
                    >
                        {{ __('All') }}
                    </button>

                    @foreach ($model::MEDIA_TYPES as $typeKey => $typeLabel)
                        <button
                            type="button"
                            wire:click="setQuickFilter('type', @js($activeType === $typeKey ? null : $typeKey))"
                            data-type-filter="{{ $typeKey }}"
                            @class([
                                'px-3 py-1.5 text-sm font-medium rounded-full transition',
                                'bg-primary-600 text-white shadow-sm' => $activeType === $typeKey,
                                'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => $activeType !== $typeKey,
                            ])
                        >
                            {{ __($typeLabel) }}
                        </button>
                    @endforeach

                    @if (count($uploadMonths))
                        <select
                            wire:change="setQuickFilter('month', $event.target.value)"
                            data-month-filter
                            class="px-3 py-1.5 ml-2 text-sm text-gray-600 bg-white rounded-lg border transition appearance-none border-gray-500/30 focus:outline-none focus:ring focus:border-primary-300 focus:ring-primary-300 focus:ring-opacity-50 dark:text-gray-300 dark:bg-transparent dark:border-gray-700"
                        >
                            <option value="" @selected($activeMonth === null)>{{ __('All dates') }}</option>
                            @foreach ($uploadMonths as $month)
                                <option value="{{ $month }}" @selected($activeMonth === $month)>
                                    {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="mb-4 ml-4 w-full max-w-64 md:mb-0">
                  @if($this->settings['table_before'])
                      @if (!empty($this->userFilters))
                        <div class="my-4 w-full">
                          <x-aura::input.select
                              wire:model.live="selectedFilter"
                              :options="collect($this->userFilters)->mapWithKeys(function ($filter, $key) {
                                  return [$key => $filter['name']];
                              })->prepend('Alle', '')"
                          >
                          </x-aura::input.select>
                      </div>
                  @else
                      <div class="mb-4 w-full"></div>
                  @endif

                  @endif
                </div>

                <div class="flex justify-end items-center space-x-4 w-full">

                    {{-- Columns --}}
                    @if ($this->settings['settings'] || $this->settings['filters'])
                        <div class="flex space-x-2">

                            @include('aura::components.table.switch-view')

                            @if ($this->settings['settings'])
                                @if ($currentView == 'list' && $model->showTableSettings())
                                    @include('aura::components.table.settings')
                                @endif
                            @endif

                            @if ($this->settings['settings'])
                                @if ($currentView == 'kanban' && $model->showTableSettings())
                                    @include('aura::components.table.kanban-settings')
                                @endif
                            @endif

                            @if ($this->settings['filters'])
                                @include($this->settings['views']['filter'])
                            @endif
                        </div>
                    @endif

                    @if ($this->settings['bulk_actions'])
                        @include($this->settings['views']['bulk_actions'])
                    @endif
                </div>
            </div>

            <div class="aura-table">
                @if($this->settings['selectable'])
                    <div wire:key="table-bulk-select">
                        @include('aura::components.table.bulk-select-row')
                    </div>
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
                @include('aura::components.table.filters')
            </x-aura::sidebar.mediamanager>
        @endif
    </main>
</div> {{-- This closes the context menu --}}
</div>
