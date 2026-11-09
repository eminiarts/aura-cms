<div @selectfieldrows.window="selectRows($event.detail)" {{-- wire:poll.10000ms --}} x-data="{
    selected: @entangle('selected'),
    rows: @js($rowIds),
    lastSelectedId: null,
    total: @js($rows->total()),
    selectPage: false,
    currentPage: @entangle('paginators.page'),
    selectAll: @entangle('selectAll'),
    loading: false,
    oldSelected: null,

    @if($field)
    selectRows(detail) {
        if (detail.slug == '{{ $field['slug'] }}') {
            this.selected = detail.value
        }
    },
    @endif

    init() {
        $wire.on('selectedRows', (updatedSelected) => {
            this.selected = updatedSelected[0];
        });

        $wire.on('rowIdsUpdated', (ids) => {
            this.rows = ids[0];
            this.selectPage = false;
        });

        if (this.selectAll) {
            this.selectPage = true;
        }

        @if($field)
        {{-- Need to refactor this maybe because it's field specific --}}
        this.$watch('selected', value => {
            // Emit an event with the new value
            {{-- console.log('dispatch selection-changed', this.selected, value); --}}
            this.$dispatch('selection-changed', { selected: value, slug: '{{ $field['slug'] }}' });
        });
        @endif

        // watch rows for changes
        this.$watch('rows', (rows) => {
            // Check if rows (array of ids) is included in this.selected. if so, set this.selectPage to true

            //this.selectPage = rows.every(row => this.selected.includes(row.toString()));
        });

        this.$watch('currentPage', (rows) => {
            this.$nextTick(() => {
                this.selectPage = this.rows.every(row => this.selected.includes(row));
            });
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

    toggleRow(event, id) {
    this.$nextTick(() => {
        if (event.shiftKey && this.lastSelectedId !== null) {
            // Handle shift-select range
            const currentIndex = this.rows.indexOf(id);
            const lastIndex = this.rows.indexOf(this.lastSelectedId);
            
            if (currentIndex !== -1 && lastIndex !== -1) {
                const start = Math.min(lastIndex, currentIndex);
                const end = Math.max(lastIndex, currentIndex);
                
                const rangeIds = this.rows.slice(start, end + 1);

                // Determine whether to select or deselect based on the current state of the clicked checkbox
                const shouldSelect = !this.selected.includes(id);

                if (shouldSelect) {
                    // Select the range
                    this.selected = [...new Set([...this.selected, ...rangeIds])];
                } else {
                    // Deselect the range
                    this.selected = this.selected.filter(selectedId => !rangeIds.includes(selectedId));
                }
            }
        } else {
            // Regular single row toggle
            if (this.selected.includes(id)) {
                // Deselect if already selected
                this.selected = this.selected.filter(selectedId => selectedId !== id);
            } else {
                // Select if not already selected
                this.selected.push(id);
            }
        }

        // Update lastSelectedId for future shift-selects
        this.lastSelectedId = id;

        // Update selectAll status
        this.selectAll = this.selected.length === this.total;

        // Update selectPage status
        this.selectPage = this.rows.every(rowId => this.selected.includes(rowId));
    });
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
        @php
            // No records at all (not a search or filter that matches nothing): there is nothing to search,
            // arrange or filter yet, so the toolbar stays away and the empty state holds the one create button.
            $collectionIsEmpty = $currentView !== 'kanban' && $rows->total() === 0
                && blank($this->search ?? null) && blank($this->selectedFilter ?? null)
                && empty(data_get($this->filters ?? [], 'custom'));
        @endphp

        @include($this->settings['views']['header'])

        @if ($this->settings['widgets'] ?? false)
            @include('aura::components.table.widgets')
        @endif

        @php
            $hasToolbar = ! $collectionIsEmpty && ($this->settings['search'] || $this->settings['settings'] || $this->settings['filters']
                || ($this->settings['bulk_actions'] && $this->bulkActions));
        @endphp

        <div @class(['mt-4' => $hasToolbar])>

            {{-- One toolbar row: search grows, the controls keep their size and end on the content edge.
                 Phones with a view switch get two rows: the search full width, then the view switch on the
                 left and the labelled Filters button (and the view's settings as a "more" menu) on the right. --}}
            @if ($hasToolbar)
            @php
                $hasViewSwitch = $model->tableGridView() || ($this->resolvedKanbanConfiguration()['enabled'] ?? false) || $model->tableKanbanView();
                $twoRowsOnPhones = $hasViewSwitch && ($this->settings['settings'] || $this->settings['filters']);
            @endphp
            <div @class(['flex gap-2 items-center w-full', 'max-sm:flex-wrap max-sm:gap-y-3' => $twoRowsOnPhones])>

                @if ($this->settings['search'])
                    <div @class(['flex-1 min-w-0 md:max-w-sm', 'max-sm:basis-full' => $twoRowsOnPhones])>
                        <label for="table-search" class="sr-only">{{ __('Search') }}</label>
                        <div class="relative">
                            <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor"
                                    viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input type="text" id="table-search"
                                class="block h-9 w-full py-2 pl-9 pr-3 text-base sm:text-sm text-gray-900 bg-white rounded-lg border-0 ring-1 shadow-xs transition appearance-none placeholder:text-gray-400 dark:placeholder:text-gray-500 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 focus:outline-none focus:ring-2 focus:ring-primary-500 z-[1]"
                                placeholder="{{ __('Search :resources…', ['resources' => Str::lower(__($model->pluralName()))]) }}" wire:model.live.debounce="search">

                        </div>
                    </div>
                @endif

                <div @class(['flex gap-2 items-center ml-auto shrink-0', 'max-sm:w-full' => $twoRowsOnPhones])>

                    {{-- Columns --}}
                    @if ($this->settings['settings'] || $this->settings['filters'])

                        <div @class(['max-sm:mr-auto' => $twoRowsOnPhones])>
                            @include('aura::components.table.switch-view')
                        </div>

                        @if ($this->settings['settings'])
                            @if ($currentView == 'list' && $model->showTableSettings())
                                @include('aura::components.table.settings')
                            @endif
                        @endif

                        {{-- Board settings: a labelled button from sm, the "more" menu at the end of the row on phones. --}}
                        @if ($this->settings['settings'])
                            @if ($currentView == 'kanban' && $model->showTableSettings())
                                <div class="max-sm:order-last">
                                    @include('aura::components.table.kanban-settings')
                                </div>
                            @endif
                        @endif

                        @if ($this->settings['filters'])
                            @include($this->settings['views']['filter'], ['labelOnPhones' => $twoRowsOnPhones])
                        @endif
                    @endif

                    @if ($this->settings['bulk_actions'])
                        @include($this->settings['views']['bulk_actions'])
                    @endif

                    @include('aura::components.table.resource-editor-button')
                </div>
            </div>
            @endif

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
