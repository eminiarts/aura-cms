<div @selectfieldrows.window="selectRows($event.detail)" {{-- wire:poll.10000ms --}} x-data="{
    selected: $wire.entangle('selected'),
    exclusions: $wire.entangle('selectAllExclusions'),
    rows: @js($rowIds),
    lastSelectedId: null,
    total: @js($rows->total()),
    selectPage: false,
    currentPage: $wire.entangle('paginators.page'),
    selectAll: $wire.entangle('selectAll'),
    loading: false,

    @if($field)
    selectRows(detail) {
        if (detail.slug == '{{ $field['slug'] }}') {
            this.applySelection({ selected: detail.value, selectAll: false, exclusions: [] });
        }
    },
    @endif

    init() {
        $wire.on('selectedRows', (updatedSelected) => {
            this.applySelection({ selected: updatedSelected[0] || [], selectAll: false, exclusions: [] });
        });

        $wire.on('rowIdsUpdated', (ids) => {
            this.rows = ids[0] || [];
            this.refreshPageSelection();
        });

        this.refreshPageSelection();

        @if($field)
        this.$watch('selected', value => {
            this.$dispatch('selection-changed', { selected: value, slug: '{{ $field['slug'] }}' });
        });
        @endif

        this.$watch('currentPage', () => {
            this.$nextTick(() => this.refreshPageSelection());
        });
    },

    applySelection(state) {
        this.selected = state.selected || [];
        this.selectAll = Boolean(state.selectAll);
        this.exclusions = state.exclusions || [];
        this.refreshPageSelection();
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

    async toggleRow(event, id) {
        let ids = [id];

        if (event.shiftKey && this.lastSelectedId !== null) {
            const currentIndex = this.rows.findIndex(row => String(row) === String(id));
            const lastIndex = this.rows.findIndex(row => String(row) === String(this.lastSelectedId));

            if (currentIndex !== -1 && lastIndex !== -1) {
                ids = this.rows.slice(Math.min(lastIndex, currentIndex), Math.max(lastIndex, currentIndex) + 1);
            }
        }

        const shouldSelect = !this.isRowSelected(id);
        this.applySelection(await $wire.updateRowSelection(ids, shouldSelect));
        this.lastSelectedId = id;
    }
}">
    {{-- Be aware that this file opens a div which closes at the end --}}
    @include('aura::components.table.context-menu')

    <main class="" x-data="{
        showFilters: false,
        toggleFilters() {
            this.showFilters = !this.showFilters;
            this.$dispatch('inset-sidebar', { element: this.$refs.sidebar })
        },
        init() {

            $wire.dispatch('tableMounted')

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

        @php
        @endphp

        <div class="mt-4">

            <div class="flex flex-col justify-between w-full md:items-center md:flex-row">

                @if ($this->settings['search'])
                    <div class="mb-4 md:mb-0">
                        <label for="table-search" class="sr-only">{{ __('Search') }}</label>
                        <div class="relative mt-1">
                            <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor"
                                    viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input type="text" id="table-search"
                                class="block py-2 pl-9 pr-3 w-64 max-w-full text-sm text-gray-900 bg-white rounded-lg border-0 ring-1 shadow-xs transition appearance-none placeholder:text-gray-400 dark:placeholder:text-gray-500 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 focus:outline-none focus:ring-2 focus:ring-primary-500 z-[1]"
                                placeholder="{{ __('Search for items') }}" wire:model.live.debounce="search">

                        </div>
                    </div>
                @endif

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
            <x-aura::sidebar title="Filters" show="showFilters">
                <x-slot:heading class="font-semibold">
                    <h3 class="text-2xl font-semibold">
                        {{ __('Filters') }}
                    </h3>
                </x-slot>
                @include('aura::components.table.filters')
            </x-aura::sidebar>
        @endif
    </main>
</div> {{-- This closes the context menu --}}
</div>
