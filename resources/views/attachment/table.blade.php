<div @selectfieldrows.window="selectRows($event.detail)" x-data="{
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

    init() {
        Livewire.on('selectedRows', (updatedSelected) => {
            this.selected = updatedSelected[0];
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
            this.$dispatch('selection-changed', { selected: value, slug: this.field ? this.field.slug : null });
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
        console.log('toggleRow called with:', { event, id });
        console.log('Current state:', { rows: this.rows, selected: this.selected, lastSelectedId: this.lastSelectedId });

        if (!this.rows || !Array.isArray(this.rows)) {
            console.warn('this.rows is not an array, exiting toggleRow');
            return;
        }

        this.$nextTick(() => {
            console.log('Inside $nextTick');

            if (this.field && this.field.max_files === 1) {
                console.log('Single file selection mode');
                if (this.selected.includes(id.toString())) {
                    console.log('Deselecting single item');
                    this.selected = [];
                } else {
                    console.log('Selecting single item');
                    this.selected = [id.toString()];
                }
            } else if (event.shiftKey && this.lastSelectedId !== null) {
                console.log('Shift key pressed, last selected id:', this.lastSelectedId);
                const lastIndex = this.rows.indexOf(this.lastSelectedId);
                const currentIndex = this.rows.indexOf(id);
                console.log('Indexes:', { lastIndex, currentIndex });

                if (lastIndex === -1 || currentIndex === -1) {
                    console.warn('Invalid indexes, exiting shift selection');
                    return;
                }

                const start = Math.min(lastIndex, currentIndex);
                const end = Math.max(lastIndex, currentIndex);
                const rowsToToggle = this.rows.slice(start, end + 1);
                console.log('Rows to toggle:', rowsToToggle);

                const isLastSelected = this.selected.includes(this.lastSelectedId.toString());
                console.log('Is last selected:', isLastSelected);

                if (isLastSelected) {
                    console.log('Adding rows to selection');
                    const newSelection = [...new Set([...this.selected, ...rowsToToggle.map(String)])];
                    if (this.field && this.field.max_files) {
                        console.log('Applying max files limit:', this.field.max_files);
                        this.selected = newSelection.slice(0, this.field.max_files);
                    } else {
                        this.selected = newSelection;
                    }
                } else {
                    console.log('Removing rows from selection');
                    this.selected = this.selected.filter(row => !rowsToToggle.includes(parseInt(row)));
                }
            } else {
                console.log('Single click selection');
                const index = this.selected.indexOf(id.toString());
                console.log('Index:', index, this.selected, id.toString());
                if (index === -1) {
                    if (!this.field || !this.field.max_files || this.selected.length < this.field.max_files) {
                        console.log('Adding item to selection');
                        this.selected.push(id.toString());
                    } else {
                        console.warn('Max files limit reached, cannot add more');
                    }
                } else {
                    console.log('Removing item from selection');
                    this.selected.splice(index, 1);
                }
            }

            this.lastSelectedId = id;
            console.log('Updated state:', { selected: this.selected, lastSelectedId: this.lastSelectedId });
        });
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

            <div class="flex flex-col justify-between w-full md:items-center md:flex-row">

                @if ($this->settings['search'])
                    <div class="mb-4 md:mb-0">
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

                <div class="mb-4 ml-4 md:mb-0">
                    <label for="file-type-filter" class="sr-only">Filter File Types</label>
                    <div class="relative mt-1">
                        <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <select id="file-type-filter"
                            class="block p-2.5 pl-10 w-64 max-w-full rounded-lg shadow-xs transition transition-300 border border-gray-500/30 appearance-none px-3 py-2 focus:outline-none ring-gray-900/10 focus:ring focus:border-primary-300 focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 disabled:opacity-75 disabled:bg-gray-100 disabled:opacity-60 disabled:dark:bg-gray-800 bg-white dark:bg-transparent border dark:border-gray-700 dark:focus:border-gray-500 z-[1]"
                            wire:model.live="fileTypeFilter">
                            <option value="">{{ __('All File Types') }}</option>
                            <option value="image">{{ __('Images') }}</option>
                            <option value="document">{{ __('Documents') }}</option>
                            <option value="video">{{ __('Videos') }}</option>
                            <option value="audio">{{ __('Audio') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
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
                        @include($this->settings['views']['bulkActions'])
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
